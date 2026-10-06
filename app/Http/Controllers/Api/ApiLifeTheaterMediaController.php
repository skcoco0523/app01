<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\LifeTheaterMedia;
use App\Models\LifeTheater;
use App\Models\LifeTheaterShare;

class ApiLifeTheaterMediaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 1. 画像ライブラリ一覧の取得 (Ajax)
     */
    public function index(Request $request, $life_theater_id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            if (!$this->checkPermission($life_theater_id)) {
                return response()->json(['status' => 'error', 'message' => 'アクセス権限がありません。'], 403);
            }

            $media = LifeTheaterMedia::where('life_theater_id', $life_theater_id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $media
            ]);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '取得に失敗しました。'], 500);
        }
    }

    /**
     * 2. S3ダイレクト送信用の署名付きURL（Presigned URL）発行
     * ※ファイル本体は受け取らず、S3への書き込み許可URLだけを返す
     */
    public function getPresignedUrl(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $life_theater_id = $request->input('life_theater_id');
            $filename        = $request->input('filename');

            if (!$life_theater_id || !$filename) {
                return response()->json(['status' => 'error', 'message' => 'パラメータが不足しています。'], 400);
            }

            if (!$this->checkPermission($life_theater_id)) {
                return response()->json(['status' => 'error', 'message' => '権限がありません。'], 403);
            }

            // S3保存パスの生成
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $s3Key     = "life_theater_media/{$life_theater_id}/" . uniqid() . ($extension ? '.' . $extension : '');

            // 5分間有効なPUT用署名付きURLを発行
            $client = Storage::disk('s3')->getClient();
            $command = $client->getCommand('PutObject', [
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key'    => $s3Key,
            ]);

            $presignedRequest = $client->createPresignedRequest($command, '+5 minutes');
            $presignedUrl     = (string) $presignedRequest->getUri();

            return response()->json([
                'status'        => 'success',
                'presigned_url' => $presignedUrl,
                's3_key'        => $s3Key,
                'public_url'    => Storage::disk('s3')->url($s3Key),
            ]);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '署名付きURLの発行に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 3. S3ダイレクト完了後のメタデータ保存、または画像名の更新
     */
    public function store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $life_theater_id = $request->input('life_theater_id');
            $media_id        = $request->input('id'); // 名前更新用のID

            if (!$life_theater_id || !$this->checkPermission($life_theater_id)) {
                return response()->json(['status' => 'error', 'message' => '権限がないか、作品IDが無効です。'], 403);
            }

            if ($media_id) {
                $media = LifeTheaterMedia::where('id', $media_id)
                    ->where('life_theater_id', $life_theater_id)
                    ->first();

                if ($media) {
                    $media->name = $request->input('name');
                    $media->save();

                    return response()->json([
                        'status'  => 'success',
                        'data'    => $media,
                        'message' => '画像名を変更しました。'
                    ]);
                }
            }

            // 新規登録（STORE）
            $input = [
                'life_theater_id' => $life_theater_id,
                'user_id'         => Auth::id(),
                'name'            => $request->input('name'),
                'image_s3_key'    => $request->input('public_url'),
                'category'        => $request->input('category', 'general'),
            ];

            $ret = LifeTheaterMedia::createMedia($input);

            if ($ret['error_code'] == 0) {
                return response()->json([
                    'status'  => 'success',
                    'data'    => $ret['media'] ?? null,
                    'message' => '画像を保存しました。'
                ]);
            }

            return response()->json(['status' => 'error', 'message' => 'データベースへの保存に失敗しました。'], 400);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'エラーが発生しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 4. 画像ライブラリからの削除 (Ajax)
     */
    public function destroy(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $media = LifeTheaterMedia::find($id);
            if (!$media) {
                return response()->json(['status' => 'error', 'message' => '対象画像が存在しません。'], 404);
            }

            if (!$this->checkPermission($media->life_theater_id)) {
                return response()->json(['status' => 'error', 'message' => '権限がありません。'], 403);
            }

            // S3上の物理ファイルを削除する
            if (!empty($media->image_s3_key)) {
                // DBに保存されているフルURLからS3内の相対パス（Key）を取り出す
                // 例: "https://...amazonaws.com/life_theater_media/1/xxx.jpg" -> "life_theater_media/1/xxx.jpg"
                $parsedPath = parse_url($media->image_s3_key, PHP_URL_PATH);
                $s3Key = ltrim($parsedPath, '/');

                if ($s3Key && Storage::disk('s3')->exists($s3Key)) {
                    Storage::disk('s3')->delete($s3Key);
                }
            }

            // DBからメタデータを削除
            $ret = LifeTheaterMedia::delMedia(['id' => $id]);

            if ($ret['error_code'] == 0) {
                return response()->json(['status' => 'success', 'message' => '削除しました。']);
            }

            return response()->json(['status' => 'error', 'message' => '削除に失敗しました。'], 400);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'エラーが発生しました。'], 500);
        }
    }

    /**
     * 権限確認ヘルパー
     */
    private function checkPermission($life_theater_id)
    {
        $user_id = Auth::id();

        $is_owner = LifeTheater::where('id', $life_theater_id)->where('user_id', $user_id)->exists();
        if ($is_owner) return true;

        $is_shared = LifeTheaterShare::where('life_theater_id', $life_theater_id)->where('user_id', $user_id)->exists();
        return $is_shared;
    }
}