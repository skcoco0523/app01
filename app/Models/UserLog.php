<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserLog extends Model
{
    use HasFactory;

    protected $table = 'user_logs';

    // マスアサインメント可能な属性
    protected $fillable = [
        'user_id',
        'ip_address',
        'type',
        'success_flag',
        'memo',
    ];

    public $timestamps = false; // タイムスタンプを無効にする

    //login, logout, prf_chg, ...
    public static function create_user_log($user_id, $log_type, $success_flag=true, $memo=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start--------");

        if($user_id && $log_type){
            make_error_log($error_log,"user_id=".$user_id." log_type=".$log_type." success_flag=".$success_flag." memo=".$memo);
            return self::Create(
                [
                    'user_id' => $user_id,
                    'ip_address' => request()->ip(),
                    'type' => $log_type,
                    'success_flag' => $success_flag,
                    'memo' => $memo,
                    'created_at' => now() // created_at を手動で設定
                ]
            );
        }else{
            make_error_log($error_log,"user_id or log_type is null");
            return null;
        }
    }

}
