<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\LifeTheaterSlide;
use App\Models\LifeTheaterShare;
use App\Models\LifeTheaterMedia;
use App\Models\User;

class LifeTheater extends Model
{
    use HasFactory;

    public const DEFAULT_CONFIG = [
        'title_size'     => 'md',   // 標準タイトルサイズ
        'subtitle_size'  => 'md',   // 標準サブタイトルサイズ
        'slide_duration' => 7500,  // 標準再生速度（7.5秒）
    ];

    protected $fillable = [
        'user_id',
        'title',
        'subtitle',
        'bgm_type',
        'theme_color_num',
        'edit_lock_flag',
        'plan_type',
        'used_points',
        'config_data',
    ];

    protected $casts = [
        'edit_lock_flag' => 'boolean',
        'config_data' => 'array',
    ];

    // リレーション: 作成者
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // リレーション: スライド一覧
    public function slides()
    {
        return $this->hasMany(LifeTheaterSlide::class)->orderBy('step_order', 'asc');
    }

    // リレーション: 共有情報
    public function shares()
    {
        return $this->hasMany(LifeTheaterShare::class);
    }

    // リレーション: 画像ライブラリ
    public function media()
    {
        return $this->hasMany(LifeTheaterMedia::class)->orderBy('created_at', 'desc');
    }

    // =========================================================================
    // データ操作メソッド
    // =========================================================================

    public static function getLifeTheaterList($disp_cnt = null, $pageing = false, $page = 1, $keyword = null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = DB::table('life_theaters as life_theater');

            if ($keyword) {
                if (get_proc_data($keyword, "admin_flag")) {
                    if (isset($keyword['search_admin_flag'])) {
                        $sql_cmd = $sql_cmd->where('life_theater.admin_flag', $keyword['search_admin_flag']);
                    }
                } else {
                    $sql_cmd = $sql_cmd->where('life_theater.user_id', Auth::id());

                    if (isset($keyword['search_life_theater_id'])) {
                        $sql_cmd = $sql_cmd->where('life_theater.id', $keyword['search_life_theater_id']);
                    }
                }

                if (get_proc_data($keyword, "cdate_asc"))  $sql_cmd = $sql_cmd->orderBy('life_theater.created_at', 'asc');
                if (get_proc_data($keyword, "udate_asc"))  $sql_cmd = $sql_cmd->orderBy('life_theater.updated_at', 'asc');
                if (get_proc_data($keyword, "cdate_desc")) $sql_cmd = $sql_cmd->orderBy('life_theater.created_at', 'desc');
                if (get_proc_data($keyword, "udate_desc")) $sql_cmd = $sql_cmd->orderBy('life_theater.updated_at', 'desc');
            } else {
                $sql_cmd = $sql_cmd->where('life_theater.user_id', Auth::id())->orderBy('life_theater.created_at', 'desc');
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
                $item->owner_flag = true;
            }

            return $sql_cmd;
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return [];
        }
    }

    public static function createLifeTheater($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $error_code = 0;
            if (!isset($data['user_id'])) $error_code = 1;
            if (!isset($data['title']))   $error_code = 2;

            if ($error_code) {
                make_error_log($error_log, "error_code=" . $error_code);
                return ['id' => null, 'error_code' => $error_code];
            }

            // ★ config_data が未指定の場合はデフォルト値をセット
            if (!isset($data['config_data'])) {
                $data['config_data'] = self::DEFAULT_CONFIG;
            }

            $request = self::create($data);
            make_error_log($error_log, "success id=" . $request->id);
            return ['id' => $request->id, 'error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];
        }
    }

    public static function chgLifeTheater($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $item = self::where('id', $data['id'])->first();
            if (!$item) {
                make_error_log($error_log, ".not found id:" . $data['id']);
                return ['id' => null, 'error_code' => -1];
            }

            if ($item->user_id != Auth::id()) {
                if (get_proc_data($data, "share_flag")) {
                    make_error_log($error_log, ".shared item user_id:" . $item->user_id);
                } else {
                    return ['id' => null, 'error_code' => -2];
                }
            }

            if ($item->edit_lock_flag && get_proc_data($data, "edit_lock_flag")) {
                return ['id' => null, 'error_code' => -3];
            }

            $updateData = [];
            if (isset($data['title']))           $updateData['title']           = $data['title'];
            if (isset($data['subtitle']))        $updateData['subtitle']        = $data['subtitle'];
            if (isset($data['bgm_type']))        $updateData['bgm_type']        = $data['bgm_type'];
            if (isset($data['theme_color_num'])) $updateData['theme_color_num'] = $data['theme_color_num'];
            if (isset($data['edit_lock_flag']))  $updateData['edit_lock_flag']  = $data['edit_lock_flag'];
            if (isset($data['config_data']))     $updateData['config_data']     = $data['config_data'];

            self::where('id', $data['id'])->update($updateData);

            make_error_log($error_log, "success");
            return ['error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['error_code' => -1];
        }
    }

    public static function delLifeTheater($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log, "delete_id=" . $data['id']);
            self::where('id', $data['id'])->delete();

            make_error_log($error_log, "success");
            return ['id' => null, 'error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];
        }
    }
}