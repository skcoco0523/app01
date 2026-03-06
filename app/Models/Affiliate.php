<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Affiliate extends Model
{
    use HasFactory;

    
    //作成
    public static function createAffiliate($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"-------start-------");
            make_error_log($error_log,"aff_link=".$data['aff_link']);
            if(!$data['aff_link'])  return ['id' => null, 'error_code' => 1];   //データ不足

            // href を取得
            preg_match('/href="([^"]+)"/', $data['aff_link'], $hrefMatches);
            $href = isset($hrefMatches[1]) ? $hrefMatches[1] : '';

            // src を取得
            preg_match_all('/src="([^"]+)"/', $data['aff_link'], $srcMatches);
            $src1 = isset($srcMatches[1][0]) ? $srcMatches[1][0] : null;  // 1つ目 画像情報
            $src2 = isset($srcMatches[1][1]) ? $srcMatches[1][1] : null;  // 2つ目 トラッキング


            //最低限トラッキングを除く2つが必須
            if(!$href || !$src1) return ['id' => null, 'error_code' => 2];   //追加失敗
            
            
            try {
                $affiliate = new Affiliate();
                $affiliate->href            = $href;
                $affiliate->src             = $src1;
                $affiliate->tracking_src    = $src2;
                $affiliate->save();
                // 保存されたレコードのIDを取得
                $aff_id = $affiliate->id;
                make_error_log($error_log,"success");
                return ['id' => $aff_id, 'error_code' => 0];   //追加成功

            } catch (\Exception $e) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
                return ['id' => null, 'error_code' => -1];   //追加失敗

            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //不正データ

        }
    }
    //変更
    public static function chgAffiliate($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"-------start-------");
            make_error_log($error_log,"aff_link=".$data['aff_link']);
            if(!$data['aff_link'])  return ['id' => null, 'error_code' => 1];   //データ不足

            // href を取得
            preg_match('/href="([^"]+)"/', $data['aff_link'], $hrefMatches);
            $new_href = isset($hrefMatches[1]) ? $hrefMatches[1] : '';

            // src を取得
            preg_match_all('/src="([^"]+)"/', $data['aff_link'], $srcMatches);
            $new_src = isset($srcMatches[1][0]) ? $srcMatches[1][0] : null;  // 1つ目 画像情報
            $new_src2 = isset($srcMatches[1][1]) ? $srcMatches[1][1] : null;  // 2つ目 トラッキング


            //最低限トラッキングを除く2つが必須
            if(!$new_href || !$new_src) return ['id' => null, 'error_code' => 2];   //追加失敗
            

            try {
                /*  クエリビルダではupdated_atが自動更新されない
                DB::table('affiliates')->where('id', $data['aff_id'])
                ->update([
                    'href' => $new_href, 
                    'src' => $new_src, 
                ]);
                */
                Affiliate::where('id', $data['aff_id'])
                    ->update([
                        'href' => $new_href, 
                        'src' => $new_src, 
                        'tracking_src' => $new_src2, 
                    ]);
                
                // 保存されたレコードのIDを取得
                make_error_log($error_log,"success");
                return ['id' => $data['aff_id'], 'error_code' => 0];   //更新成功

            } catch (\Exception $e) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
                return ['id' => null, 'error_code' => -1];   //更新失敗

            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => 2];   //不正データ

        }
    }
    //削除
    public static function delAffiliate($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            //他データはリレーションでカスケード削除
            make_error_log($error_log,"delete_aff_id=".$data['aff_id']);
            Affiliate::where('id', $data['aff_id'])->delete();

            make_error_log($error_log,"success");
            return ['id' => null, 'error_code' => 0];   //削除成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //削除失敗

        }
    }
}
