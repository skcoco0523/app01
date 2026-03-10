<?php
// app/Helpers/CustomFunctions.php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;


//エラーログ
if (! function_exists('make_error_log')) {
    /**
     * Custom function to make error log.
     *
     * @param  string  $file_name
     * @param  mixed  $prm
     * @return void
     */
    //sudo groupadd bitnami-daemon でｸﾞﾙｰﾌﾟ作成
    //sudo usermod -aG bitnami-daemon bitnami でｸﾞﾙｰﾌﾟに追加
    //sudo usermod -aG bitnami-daemon daemon でｸﾞﾙｰﾌﾟに追加
    //getent group | grep bitnami でｸﾞﾙｰﾌﾟ確認
    //sudo chown -R :bitnami-daemon /opt/bitnami/apache/htdocs/app01/storage　でstorageの設定変更
    //sudo chmod g+s /home/bitnami/htdocs/app01/storage/logs 新たに作成されたファイルやディレクトリは、logs ディレクトリと同じグループ

    function make_error_log($file_name, $prm)
    {
        $config_path = config('error.log.path', 'errorlog');
        $file_path = storage_path("$config_path/$file_name");
        $file_path_old = storage_path("$config_path/{$file_name}_old");
        
    
        // サイズチェック用の閾値（10MB）
        $max_mb = 10;
        $max_size = 1024 * 1024 * $max_mb;

        try {
            // 10Mになった場合はファイルを変更f775 
            if (File::exists($file_path) && filesize($file_path) >= $max_size) {
                // 古いファイルを削除
                if (File::exists($file_path_old)) File::delete($file_path_old);
                // 現在のログをリネーム
                File::move($file_path, $file_path_old);
            }

            // ファイルが存在しない場合は新規作成
            if (!File::exists($file_path)) {
                // 必要に応じてディレクトリも作成
                $directory = dirname($file_path);
                if (!File::isDirectory($directory))
                    File::makeDirectory($directory, 0775, true);
                
                // 空のファイルを作成
                File::put($file_path, '');
                chmod($file_path, 0664);
            }

            $log = \Illuminate\Support\Facades\Log::build([
                'driver' => 'single',
                'path' => $file_path,
                // 「local.DEBUG: 」をなくしメッセージのみを出力するフォーマッターを指定
                'formatter' => \Monolog\Formatter\LineFormatter::class,
                'formatter_with' => [
                    'format' => "%message%\n", // 日付や local.DEBUG を除外し、メッセージと改行のみにする
                    'allowInlineLineBreaks' => true,
                ],
            ]);

            $log->debug($prm);

        } catch (\Exception $e) {
            // ログの処理中にエラーが発生した場合、例外を投げる
            throw new \RuntimeException("Error handling log file: " . $e->getMessage(), 0, $e);
        }
    }
}

//リクエストからデータ取得
if (! function_exists('get_proc_data')) {
    /**
     * Custom function to make error log.
     *
     * @param  string  $file_name
     * @param  mixed  $prm
     * @return void
     */
    function get_proc_data($input, $key)
    {
        $value = $input[$key] ?? null;
        // 値が存在し、かつ空でない場合、その値を返す
        if (isset($value) && $value !== '') {
            return $value;
        }
        // 値がnullまたは空の場合はnullを返す
        return null;        
    }
}

//画像情報付与 aff_idに対して、画像格納を合わせて返す 20240122 kanno
if (! function_exists('setAffData')) {
    function setAffData($obj) {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try{
            //id プロパティが存在する場合の処理
            if (!empty($obj->id)){
                $aff_data = DB::table('affiliates as aff')
                ->where('id', '=', $obj->aff_id)
                ->first();
                if ($aff_data) {
                    $obj->href          = $aff_data->href;
                    $obj->src           = $aff_data->src;
                    $obj->tracking_src  = $aff_data->tracking_src;
                } else {
                    // $aff_data が null の場合
                    $obj->href          = NULL;
                    $obj->src           = asset('img/pic/no_image.png');
                    $obj->tracking_src  = asset('img/pic/no_image.png');
                }
            }else{
                foreach ($obj as $val) {
                    //コレクション内のaff_idが配列
                    if(is_array($val->aff_id)){
                        $aff_array = array(); 
                        
                        foreach ($val->aff_id as $aff_id) {
                            
                            $aff_data = DB::table('affiliates as aff')
                            ->where('id', '=', $aff_id)
                            ->first();
                            if ($aff_data) {
                                $href_array[]           = $aff_data->href;
                                $src_array[]            = $aff_data->src;
                                $tracking_src_array[]   = $aff_data->tracking_src;
                            } else {
                                // $aff_data が null の場合
                                $href_array[]           = NULL;
                                $src_array[]            = asset('img/pic/no_image.png');
                                $tracking_src_array[]   = asset('img/pic/no_image.png');
                            }
                            /*
                            $href_array[] = $href;
                            $src_array[] = $src;
                            if($aff_data->isEmpty()){
                                //$aff_array[] = "pic/no_image.png";
                                $aff_array[] = "pic/no_image.png";
                            }
                            */
                        }
                        $val->href          = $href_array;
                        $val->src           = $src_array;
                        $val->tracking_src  = $tracking_src_array;
                    //コレクション内のaff_idがオブジェクト
                    }else{
                        $aff_data = DB::table('affiliates as aff')
                        ->where('id', '=', $val->aff_id)
                        ->first();
                        if ($aff_data) {
                            $href           = $aff_data->href;
                            $src            = $aff_data->src;
                            $tracking_src   = $aff_data->tracking_src;
                        } else {
                            // $aff_data が null の場合
                            $href           = null;
                            $src            = asset('img/pic/no_image.png');
                            $tracking_src   = asset('img/pic/no_image.png');
                        }
                        $val->href          = $href;
                        $val->src           = $src;
                        $val->tracking_src  = $tracking_src;
                    }
                }
            }
            return $obj;

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());

            return null;
        }
    }
}