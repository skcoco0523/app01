<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\LifeTheaterMedia;
use App\Models\LifeTheater;
use App\Models\LifeTheaterShare;
use App\Models\CommonConfig;

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
            
            // 上限枚数の取得
            $theater = LifeTheater::find($life_theater_id);
            $maxLimit = $theater->getMediaLimit();

            return response()->json([
                'status' => 'success',
                'data'   => $media,
                'current_count' => $media->count(),
                'max_limit'     => $maxLimit
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
            // ==========================================================
            // ★ 追加: 画像ライブラリの上限枚数チェック
            // ==========================================================
            $theater = LifeTheater::find($life_theater_id);
            if (!$theater) {
                return response()->json(['status' => 'error', 'message' => '対象の作品が見つかりません。'], 404);
            }

            $currentMediaCount = LifeTheaterMedia::where('life_theater_id',$life_theater_id)->count();
            $maxMediaLimit =$theater->getMediaLimit();

            if ($currentMediaCount >=$maxMediaLimit) {
                return response()->json([
                    'status' => 'error', 
                    'message' => "画像ライブラリの上限（{$maxMediaLimit}枚）に達しています。\n不要な画像を削除するか、枠を拡張してください。"
                ], 400);
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
            // ==========================================================
            // 新規登録（STORE）時の念のための上限チェック getPresignedUrl で弾かれますが、API直叩きのガードとして
            // ==========================================================
            $theater = LifeTheater::find($life_theater_id);
            $currentMediaCount = LifeTheaterMedia::where('life_theater_id',$life_theater_id)->count();
            $maxMediaLimit =$theater->getMediaLimit();

            if ($currentMediaCount >=$maxMediaLimit) {
                return response()->json([
                    'status' => 'error', 
                    'message' => "画像ライブラリの上限（{$maxMediaLimit}枚）に達しています。"
                ], 400);
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
     * 画像ライブラリの上限を拡張する (Ajax - DBトランザクション保護付き)
     */
    public function expandLimit(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $lifeTheaterId = $request->input('life_theater_id');
            $user = Auth::user();

            if (!$lifeTheaterId) {
                return response()->json(['status' => 'error', 'message' => '対象の作品が指定されていません。'], 400);
            }

            if (!$this->checkPermission($lifeTheaterId)) {
                return response()->json(['status' => 'error', 'message' => '権限がありません。'], 403);
            }

            // ★ CommonConfigから設定値を取得
            $common_conf_names = [
                'po_life_theater_library'
            ];
            $configs = CommonConfig::getValues($common_conf_names);

            $po_cost   = $configs['po_life_theater_library']->value1 ?? 100;
            $expandCount = 10;

            // トランザクション処理（ポイント引き落とし ＋ 上限拡張）
            $result = DB::transaction(function () use ($lifeTheaterId, $user, $po_cost, $expandCount) {
                // ポイントチェック
                $check = $user->po_check($po_cost);
                if (!$check['can_use']) {
                    throw new \Exception('ポイントが不足しています。（必要: ' . $po_cost . 'pt）');
                }

                // ポイント消費
                $poResult = $user->po_use($po_cost, false, "ライフシアター 画像枠拡張 (+{$expandCount}枚)");
                if (!$poResult['success']) {
                    throw new \Exception('ポイントの引き落としに失敗しました。');
                }

                // 作品の拡張枠を加算
                $theater = LifeTheater::lockForUpdate()->find($lifeTheaterId);
                $theater->extra_media_limit += $expandCount;
                $theater->save();

                return [
                    'new_limit'   => $theater->getMediaLimit(),
                    'total_point' => $user->fresh()->total_point
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => "枠を {$expandCount} 枚拡張しました！",
                'data'    => $result
            ]);

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            // Exceptionのメッセージが「ポイントが不足しています」等の場合はそれをそのまま返す
            $msg = str_contains($e->getMessage(), 'ポイント') ? $e->getMessage() : '拡張処理中にエラーが発生しました。';
            return response()->json(['status' => 'error', 'message' => $msg], 400);
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