<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Advertisement extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'aff_id', 'memo', 'type', 'sdate', 'days', 'age', 'gender', 'priority', 'disp_flag'];
    protected $table = 'advertisement';
    protected static $valid_types = ['top', 'banner', 'footer', 'in_contents', 'popup'];           //必要があれば追加する

    //広告一覧取得
    public static function getAdv_list($disp_cnt=null,$pageing=false,$page=1,$keyword=null)
    {
        $sql_cmd = DB::table('advertisement as adv');
        if($keyword){
            
            //管理者による検索
            if(get_proc_data($keyword,"admin_flag")){
                if(get_proc_data($keyword,"search_name"))
                    $sql_cmd = $sql_cmd->where('adv.name', 'like', '%'. $keyword['search_name']. '%');
                if(get_proc_data($keyword,"search_click_cnt_s"))
                    $sql_cmd = $sql_cmd->where('adv.click_cnt','>=' ,$keyword['search_click_cnt_s']);
                if(get_proc_data($keyword,"search_click_cnt_e"))
                    $sql_cmd = $sql_cmd->where('adv.click_cnt','<=' , $keyword['search_click_cnt_e']);
                if(get_proc_data($keyword,"search_type"))
                    $sql_cmd = $sql_cmd->where('adv.type', $keyword['search_type']);
                if(get_proc_data($keyword,"search_month"))
                    $sql_cmd = $sql_cmd->where('adv.sdate', 'like', $keyword['search_month']. '-%');
                if(get_proc_data($keyword,"search_day"))
                    $sql_cmd = $sql_cmd->where('adv.sdate', 'like', '%-'. $keyword['search_day']. '%');
                if(get_proc_data($keyword,"search_days"))
                    $sql_cmd = $sql_cmd->where('adv.days', $keyword['search_days']);
                if(get_proc_data($keyword,"search_age"))
                    $sql_cmd = $sql_cmd->where('adv.age', $keyword['search_age']);
                if(get_proc_data($keyword,"search_gender"))
                    $sql_cmd = $sql_cmd->where('adv.gender', $keyword['search_gender']);
                if(get_proc_data($keyword,"search_disp_flag"))
                    $sql_cmd = $sql_cmd->where('adv.disp_flag', $keyword['search_disp_flag']);
                
            //ユーザーによる検索
            }else{

            }
            //並び順
            if(get_proc_data($keyword,"name_asc"))      $sql_cmd = $sql_cmd->orderBy('adv.name',        'asc');
            if(get_proc_data($keyword,"type_asc"))      $sql_cmd = $sql_cmd->orderBy('adv.type',        'asc');
            if(get_proc_data($keyword,"sdate_asc"))     $sql_cmd = $sql_cmd->orderBy('adv.sdate',       'asc');
            if(get_proc_data($keyword,"cdate_asc"))     $sql_cmd = $sql_cmd->orderBy('adv.created_at',  'asc');
            if(get_proc_data($keyword,"udate_asc"))     $sql_cmd = $sql_cmd->orderBy('adv.updated_at',  'asc');
            
            if(get_proc_data($keyword,"name_desc"))     $sql_cmd = $sql_cmd->orderBy('adv.name',        'desc');
            if(get_proc_data($keyword,"type_desc"))     $sql_cmd = $sql_cmd->orderBy('adv.type',        'desc');
            if(get_proc_data($keyword,"sdate_desc"))    $sql_cmd = $sql_cmd->orderBy('adv.sdate',       'desc');
            if(get_proc_data($keyword,"cdate_desc"))    $sql_cmd = $sql_cmd->orderBy('adv.created_at',  'desc');
            if(get_proc_data($keyword,"udate_desc"))    $sql_cmd = $sql_cmd->orderBy('adv.updated_at',  'desc');
        }

        // ページング・取得件数指定・全件で分岐
        if ($pageing){
            if ($disp_cnt === null) $disp_cnt=5;
            $sql_cmd = $sql_cmd->paginate($disp_cnt, ['*'], 'page', $page);
        }                       
        elseif($disp_cnt !== null)          $sql_cmd = $sql_cmd->limit($disp_cnt)->get();
        else                                $sql_cmd = $sql_cmd->get();

        $advertisement = $sql_cmd;

        //画像情報を付与
        $advertisement=setAffData($advertisement);
        return $advertisement; 
    }
    //ユーザー表示用広告取得
    public static function getUserAdvList($disp_cnt=null,$keyword=null)
    {
        $sql_cmd = DB::table('advertisement as adv');
        if($keyword){
            
            if (isset($keyword['search_type'])) 
                $sql_cmd = $sql_cmd->where('adv.type', $keyword['search_type']);
            
            // days が null か、または現在の日付に達しているか
            $sql_cmd = $sql_cmd->where(function($query) {
                $query->whereRaw('CURDATE() BETWEEN STR_TO_DATE(CONCAT(YEAR(CURDATE()), "-", sdate), "%Y-%m-%d") AND DATE_ADD(STR_TO_DATE(CONCAT(YEAR(CURDATE()), "-", sdate), "%Y-%m-%d"), INTERVAL days DAY)')
                    ->orwhere('adv.days', null);
                    
            });
            $sql_cmd = $sql_cmd->where('adv.disp_flag', 1);
            $sql_cmd = $sql_cmd->orderBy('adv.priority', 'asc');
            
            //ログインしている場合、表示対象を絞る
            if (Auth::check()){
                $user = Auth::user();
                //性別チェック
                if($user->gender !== null){
                    $sql_cmd = $sql_cmd->where(function($query) use ($user) { 
                        $query->where('adv.gender', $user->gender)->orwhere('adv.gender', null);
                    });
                }
                //年齢チェック
                if($user->birthdate !== null){
                    $user_age = (int)((date_diff(date_create($user->birthdate), date_create('now'))->y) / 10) * 10; // 年齢を10で割って整数部分を取得
                    $sql_cmd = $sql_cmd->where(function($query) use ($user_age) { 
                        $query->where('adv.age', $user_age)->orwhere('adv.age', null);
                     });
                }
            }
        }

        // 取得件数指定・全件で分岐                
        if($disp_cnt !== null)              $sql_cmd = $sql_cmd->limit($disp_cnt)->get();
        else                                $sql_cmd = $sql_cmd->get();
        $advertisement = $sql_cmd;

        //画像情報を付与
        $advertisement=setAffData($advertisement);

        return $advertisement; 
    }
    //広告クリック数変更
    public static function advCountUp($adv_id)
    {
        $sql_cmd = DB::table('advertisement as adv')->where('id', $adv_id)->increment('click_cnt'); // click_cntを1増やす
    }

    //作成
    public static function createAdv($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");

        //データチェック
        if(!isset($data['name']))       return ['id' => null, 'error_code' => 1];   //データ不足
        if(!isset($data['aff_id']))     return ['id' => null, 'error_code' => 2];   //データ不足
        if(!isset($data['type']))       return ['id' => null, 'error_code' => 3];   //データ不正
        if (!in_array($data['type'], self::$valid_types))  return ['id' => null, 'error_code' => 3];   //データ不正

        //いずれかが引き渡されたらチェック
        if(isset($data['month']) || isset($data['day']) || isset($data['days'])) {
            if(!isset($data['month']) || !isset($data['day']) || !isset($data['days']))
                return ['id' => null, 'error_code' => 4];   //データ不正(時間指定でどちらかがなし)
            if (!Advertisement::check_date($data['month'],$data['day'])) 
                return ['id' => null, 'error_code' => 4]; // データ不正（sdateがMM-DD形式ではない）
            if (is_numeric($data['days']) && $data['days'] > 99) 
                return ['id' => null, 'error_code' => 4]; // データ不正（daysが指定されていない）

            $data['sdate'] = str_pad($data['month'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($data['day'], 2, '0', STR_PAD_LEFT);
        }


        //DB追加処理チェック
        try {
            // DBに追加
            $Advertisement = self::create($data);
            $adv_id = $Advertisement->id;
            make_error_log($error_log,"success");
            return ['id' => $adv_id, 'error_code' => 0];   //追加成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //追加失敗
        }
    }
    //更新
    public static function chgAdv($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");

        //データチェック
        if(!isset($data['name']))       return ['id' => null, 'error_code' => 1];   //データ不足
        //if(!isset($data['aff_id']))     return ['id' => null, 'error_code' => 2];   //データ不足  更新は必須ではない
        if(!isset($data['type']))       return ['id' => null, 'error_code' => 3];   //データ不正
        if (!in_array($data['type'], self::$valid_types))  return ['id' => null, 'error_code' => 3];   //データ不正

        //いずれかが引き渡されたらチェック
        if(isset($data['month']) || isset($data['day']) || isset($data['days'])) {
            if(!isset($data['month']) || !isset($data['day']) || !isset($data['days']))
                return ['id' => null, 'error_code' => 4];   //データ不正(時間指定でどちらかがなし)
            if (!Advertisement::check_date($data['month'],$data['day'])) 
                return ['id' => null, 'error_code' => 4]; // データ不正（sdateがMM-DD形式ではない）
            if (is_numeric($data['days']) && $data['days'] > 99) 
                return ['id' => null, 'error_code' => 4]; // データ不正（daysが指定されていない）

            $data['sdate'] = str_pad($data['month'], 2, '0', STR_PAD_LEFT) . "-" . str_pad($data['day'], 2, '0', STR_PAD_LEFT);
        }else{
            $data['sdate'] = null;
        }

        //DB追加処理チェック
        try {

            $adv = Advertisement::where('id', $data['id'])->first();
            if ($adv) {
                // 更新対象となるカラムと値を連想配列に追加
                $updateData = []; 
                if ($adv->name != $data['name'])            $updateData['name']         = $data['name']; 
                if ($adv->memo != $data['memo'])            $updateData['memo']         = $data['memo']; 
                if ($adv->type != $data['type'])            $updateData['type']         = $data['type']; 
                if ($adv->sdate != $data['sdate'])          $updateData['sdate']        = $data['sdate']; 
                if ($adv->days != $data['days'])            $updateData['days']         = $data['days']; 
                if ($adv->age != $data['age'])              $updateData['age']          = $data['age']; 
                if ($adv->gender != $data['gender'])        $updateData['gender']       = $data['gender']; 
                if ($adv->priority != $data['priority'])    $updateData['priority']     = $data['priority']; 
                if ($adv->disp_flag != $data['disp_flag'])  $updateData['disp_flag']    = $data['disp_flag']; 

                make_error_log($error_log,"chg_data=".print_r($updateData,1));
                if(count($updateData) > 0){
                    Advertisement::where('id', $data['id'])->update($updateData);
                    make_error_log($error_log,"success");
                }
                return ['id' => $data['id'], 'error_code' => 0];   //更新成功

            } else {
                return ['id' => null, 'error_code' => -1];   //更新失敗
            }

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //更新失敗
        }
    }

    public static function check_date($month, $day) {
        // 月の範囲チェック
        if ($month < 1 || $month > 12) 
            return false;
        // 各月の日数チェック
        $daysInMonth = [ 1 => 31, 2 => 29, 3 => 31, 4 => 30, 5 => 31, 6 => 30, 7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31, ];
        // 日の範囲チェック
        if ($day < 1 || $day > $daysInMonth[$month]) 
            return false;

        return true;
    }
    //削除
    public static function delAdv($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            
            Advertisement::where('id', $data['id'])->delete();
            return ['id' => null, 'error_code' => 0];   //削除成功
            
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //削除失敗

        }
    }



}
