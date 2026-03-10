<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str; // Str::uuid() を使うために必要

class AdminAnotherController extends Controller
{
    
    private $memo_dir_path;
    
    public function __construct()
    {
        $this->middleware('auth');
        $this->memo_dir_path = config('common.admin_memo_path');

        // ディレクトリが存在しない場合は作成
        File::makeDirectory($this->memo_dir_path, 0755, true, true);
    }

    //メモ一覧ページ
    public function index(Request $request)
    {

        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        $input['search_title']               = get_proc_data($input, "search_title");
        $input['search_content']             = get_proc_data($input, "search_content");

        $admin_memo_list = []; // 表示するメモのリスト
        $msg = request('msg');

        // ディレクトリ内の全てのJSONファイルを読み込む
        // filter(fn($file) => $file->getExtension() === 'json') でJSONファイルのみを対象
        $memo_files = File::files($this->memo_dir_path); // 全ファイルパスのSplFileInfoオブジェクトの配列を取得

        foreach ($memo_files as $file) {
            if ($file->getExtension() === 'json') {
                $content = File::get($file->getPathname()); // ファイルの中身を読み込む
                $memo_data = json_decode($content, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($memo_data['id'])) {

                    // ファイルの更新日時（タイムスタンプ）を取得
                    $fileLastModifiedTimestamp = $file->getMTime();
                    $memo_data['updated_at'] = date('Y-m-d H:i:s', $fileLastModifiedTimestamp);

                    // 検索キーワードがある場合はフィルタリング
                    $search_check = true;
                    if ($input['search_title']) {
                        $search_check = stripos($memo_data['title'], $input['search_title']) !== false;
                    }
                    if ($input['search_content']) {
                        $search_check = stripos($memo_data['content'] ?? '', $input['search_content']) !== false;
                    }
                    if($search_check) $admin_memo_list[] = (object) $memo_data;

                } else {
                    // JSONデコードエラーまたは必須キーがない場合の処理
                    $msg = "ファイル '{$file->getFilename()}' の読み込み中にエラーが発生しました。";
                    // Log::error("メモファイルJSONデコードエラー: {$file->getFilename()} - " . json_last_error_msg());
                }
            }
        }
        
        // （オプション）メモをタイトル順などでソートする場合
        usort($admin_memo_list, fn($a, $b) => strcmp($a->title ?? '', $b->title ?? ''));

        return view('admin.admin_home', compact('admin_memo_list', 'input', 'msg'));
    }

    //メモ追加
    public function store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input = $request->all();
        $msg=null;

        // ユニークなIDを生成
        $id = (string) Str::uuid();
        // メモのデータをPHP配列として準備
        $memo_data = [
            'id'      => $id,
            'title'   => $input['title'],
            'content' => $input['content'] ?? '', // contentがnullの場合に備える
        ];

        // ファイルパスを決定 (例: storage/app/admin_memo/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.json)
        $filePath = $this->memo_dir_path . '/' . $id . '.json';

        try {
            // メモデータをJSON形式でファイルに書き込む
            File::put($filePath, json_encode($memo_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $msg = 'タイトル：'. $input['title']. 'をメモを登録しました。';
            // 登録成功後、入力フォームをクリアするために、$input の title と content を空にする
            $input['title'] = '';
            $input['content'] = '';

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            $msg = 'メモの登録に失敗しました。';
            // エラーログの出力 (必要であれば)
            // \Log::error('メモ登録エラー: ' . $e->getMessage() . ' - Data: ' . json_encode($memo_data));
        }

        return redirect()->route('admin.memo.index', ['input' => $input, 'msg' => $msg]);

    }

    //メモ削除
    public function destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input = $request->all();
        $input['id']               = get_proc_data($input, "id");
        $msg=null;

        // ファイルパスを決定 (例: storage/app/admin_memo/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.json)
        $filePath = $this->memo_dir_path . '/' . $input['id'] . '.json';

        try {
            // ファイルが存在するか確認
            if (File::exists($filePath)) {
                // メモファイルを削除
                File::delete($filePath);
                $msg = 'メモを削除しました。';
            } else {
                $msg = '削除対象のメモが見つかりませんでした。';
            }

        } catch (\Exception $e) {
            $msg = 'メモの削除に失敗しました。';
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            // エラーログの出力 (必要であれば)
            // \Log::error('メモ登録エラー: ' . $e->getMessage() . ' - Data: ' . json_encode($memo_data));
        }

        return redirect()->route('admin.memo.index', ['input' => $input, 'msg' => $msg]);

    }

    //メモ更新
    public function update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input = $request->all();
        $input['id']                = get_proc_data($input, "id");
        $input['title']             = get_proc_data($input, "title");
        $input['content']           = get_proc_data($input, "content");
        $msg=null;

        // ファイルパスを決定 (例: storage/app/admin_memo/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx.json)
        $filePath = $this->memo_dir_path . '/' . $input['id'] . '.json';

        try {
            // ファイルが存在するか確認
            if (File::exists($filePath)) {
                // メモファイルを更新
                
                // メモデータをPHP配列として準備
                $memo_data = [
                    'id'      => $input['id'],      // IDは変更しない
                    'title'   => $input['title'],
                    'content' => $input['content'],
                ];

                // メモデータをJSON形式でファイルに書き込む（上書き保存）
                File::put($filePath, json_encode($memo_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $msg = 'メモを更新しました。';
            } else {
                $msg = '更新対象のメモが見つかりませんでした。';
            }

        } catch (\Exception $e) {
            $msg = 'メモの更新に失敗しました。';
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            // エラーログの出力 (必要であれば)
            // \Log::error('メモ登録エラー: ' . $e->getMessage() . ' - Data: ' . json_encode($memo_data));
        }
        // 更新成功後、入力フォームをクリアするために、$input の title と content を空にする
        $input['title'] = '';
        $input['content'] = '';
        return redirect()->route('admin.memo.index', ['input' => $input, 'msg' => $msg]);

    }

    

}
