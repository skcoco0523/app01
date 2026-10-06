<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LifeTheaterShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'life_theater_id',
        'user_id',
        'admin_flag',
    ];

    protected $casts = [
        'admin_flag' => 'boolean',
    ];

    // 共有された作品一覧の取得
    public static function getSharedLifeTheaterList($disp_cnt = null, $pageing = false, $page = 1, $keyword = null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = DB::table('life_theater_shares as share')
                ->leftJoin('life_theaters as theater', 'share.life_theater_id', '=', 'theater.id')
                ->leftJoin('users as user', 'theater.user_id', '=', 'user.id')
                ->select('theater.*', 'theater.id as life_theater_id', 'share.admin_flag', 'share.id as share_id', 'share.user_id as user_id', 'user.id as owner_user_id', 'user.name as owner_name');

            if ($keyword) {
                if (get_proc_data($keyword, "admin_flag")) {
                    if (isset($keyword['search_admin_flag'])) {
                        $sql_cmd = $sql_cmd->where('share.admin_flag', $keyword['search_admin_flag']);
                    }
                } else {
                    $sql_cmd = $sql_cmd->where('share.user_id', Auth::id());

                    if (isset($keyword['search_life_theater_id'])) {
                        $sql_cmd = $sql_cmd->where('share.life_theater_id', $keyword['search_life_theater_id']);
                    }
                }
            } else {
                $sql_cmd = $sql_cmd->where('share.user_id', Auth::id());
            }

            if ($pageing) {
                if ($disp_cnt === null) $disp_cnt = 10;
                $sql_cmd = $sql_cmd->paginate($disp_cnt, ['*'], 'page', $page);
            } elseif ($disp_cnt !== null) {
                $sql_cmd = $sql_cmd->limit($disp_cnt)->get();
            } else {
                $sql_cmd = $sql_cmd->get();
            }

            foreach ($sql_cmd as $item) {
                $item->owner_flag = false;
            }

            return $sql_cmd;
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return [];
        }
    }
    
    // 共有しているライフシアター一覧取得（所有者側から共有相手を取得）
    public static function getSharingLifeTheaterList($disp_cnt = null, $pageing = false, $page = 1, $keyword = null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = DB::table('life_theater_shares as share')
                ->leftJoin('life_theaters as theater', 'share.life_theater_id', '=', 'theater.id')
                ->leftJoin('users as user', 'share.user_id', '=', 'user.id')
                ->select(
                    'theater.*',
                    'theater.id as life_theater_id',
                    'share.admin_flag',
                    'share.id as life_theater_share_id',
                    'theater.user_id as user_id',
                    'user.id as share_user_id',
                    'user.name as share_user_name'
                );

            if ($keyword) {
                if (get_proc_data($keyword, "admin_flag")) {
                    if (isset($keyword['search_admin_flag'])) {
                        $sql_cmd = $sql_cmd->where('share.admin_flag', $keyword['search_admin_flag']);
                    }
                } else {
                    $sql_cmd = $sql_cmd->where('theater.user_id', Auth::id());

                    if (isset($keyword['search_life_theater_id'])) {
                        $sql_cmd = $sql_cmd->where('theater.id', $keyword['search_life_theater_id']);
                    }

                    if (isset($keyword['search_friend_id'])) {
                        $sql_cmd = $sql_cmd->where('share.user_id', $keyword['search_friend_id']);
                    }
                }
            }

            if ($pageing) {
                if ($disp_cnt === null) $disp_cnt = 5;
                $sql_cmd = $sql_cmd->paginate($disp_cnt, ['*'], 'page', $page);
            } elseif ($disp_cnt !== null) {
                $sql_cmd = $sql_cmd->limit($disp_cnt)->get();
            } else {
                $sql_cmd = $sql_cmd->get();
            }

            foreach ($sql_cmd as $item) {
                $item->owner_flag = true;
            }

            return $sql_cmd;
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return [];
        }
    }

    // 共有登録
    public static function createShareLifeTheater($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            if (!isset($data['user_id']) || !isset($data['life_theater_id'])) {
                return ['id' => null, 'error_code' => 1];
            }

            $request = self::create($data);
            return ['id' => $request->id, 'error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];
        }
    }

    // 共有解除
    public static function delShareLifeTheater($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            self::where('id', $data['id'])->delete();
            return ['id' => null, 'error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];
        }
    }
}