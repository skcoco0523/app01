<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GameWeapon extends Model
{
    use HasFactory;

    protected $table = 'game_weapons';

    protected $fillable = [
        'game_id', 'weapon_key', 'name', 'type', 'custom_settings', 
        'sort_order', 'enable_flag', 'login_user_flag', 'admin_only_flag'
    ];

    protected $casts = [
        'custom_settings' => 'array',
    ];

    public function game() {
        return $this->belongsTo(GameList::class, 'game_id');
    }

    public static function getWeaponList($disp_cnt=null, $pageing=false, $page=1, $keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = self::query();
            if ($keyword) {
                //管理者による検索
                if (get_proc_data($keyword, "admin_flag")) {
                    if (get_proc_data($keyword, "search_game_id")) $sql_cmd = $sql_cmd->where('game_id', $keyword['search_game_id']);
                //ユーザーによる検索
                } else {
                    if (get_proc_data($keyword, "search_game_id")) $sql_cmd = $sql_cmd->where('game_id', $keyword['search_game_id']);
                    $sql_cmd = $sql_cmd->where('enable_flag', true);
                }
            }
            $sql_cmd = $sql_cmd->orderBy('sort_order', 'asc');

            if ($pageing) {
                if ($disp_cnt === null) $disp_cnt = 15;
                $sql_cmd = $sql_cmd->paginate($disp_cnt, ['*'], 'page', $page);
            } elseif ($disp_cnt !== null) {
                $sql_cmd = $sql_cmd->limit($disp_cnt)->get();
            } else {
                $sql_cmd = $sql_cmd->get();
            }
            return $sql_cmd;
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return collect();
        }
    }

    /**
     * 🌟 武器データの登録・更新処理をモデルに集約
     */
    public static function saveWeapon($data, $gameId)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            if (empty($data['id'])) {
                // 識別キーの重複チェック
                $exists = self::where('game_id', $gameId)->where('weapon_key', $data['weapon_key'])->exists();
                if ($exists) {
                    return ['success' => false, 'msg' => "エラー：識別キー [{$data['weapon_key']}] は既に登録されています。"];
                }

                self::create([
                    'game_id'         => $gameId,
                    'weapon_key'      => $data['weapon_key'],
                    'name'            => $data['name'],
                    'type'            => $data['type'],
                    'sort_order'      => $data['sort_order'] ?? 0,
                    'custom_settings' => json_decode($data['custom_settings_json'] ?? '{}', true) ?? [],
                    'enable_flag'     => $data['enable_flag'] ?? false,
                    'login_user_flag' => $data['login_user_flag'] ?? false,
                    'admin_only_flag' => $data['admin_only_flag'] ?? false,
                ]);

                return ['success' => true, 'msg' => "新規武器 [{$data['name']}] を追加しました！"];

            } else {
                $weapon = self::findOrFail($data['id']);
                $weapon->update([
                    'name'            => $data['name'],
                    'type'            => $data['type'],
                    'sort_order'      => $data['sort_order'] ?? 0,
                    'custom_settings' => json_decode($data['custom_settings_json'] ?? '{}', true) ?? [],
                    'enable_flag'     => $data['enable_flag'] ?? false,
                    'login_user_flag' => $data['login_user_flag'] ?? false,
                    'admin_only_flag' => $data['admin_only_flag'] ?? false,
                ]);

                return ['success' => true, 'msg' => "武器 [{$weapon->name}] を更新しました。"];
            }
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }
}