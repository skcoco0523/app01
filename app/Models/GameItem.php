<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GameItem extends Model
{
    use HasFactory;

    protected $table = 'game_items';

    protected $fillable = [
        'game_id', 'item_key', 'name', 'type',
        'sprite_sheet_id', 'custom_settings', 
        'sort_order', 'enable_flag', 'login_user_flag', 'admin_only_flag'
    ];

    protected $casts = [
        'custom_settings' => 'array',
    ];

    // 🌟 物理カラム削除に伴うカスタムセッティングへのアクセサ
    public function getGridWAttribute() { return $this->custom_settings['grid_w'] ?? 1; }
    public function getGridHAttribute() { return $this->custom_settings['grid_h'] ?? 1; }
    public function getAtlasFrameAttribute() { return $this->custom_settings['atlas_frame'] ?? ''; }

    public function game() {
        return $this->belongsTo(GameList::class, 'game_id');
    }

    public static function getItemList($disp_cnt=null, $pageing=false, $page=1, $keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = self::query();
            if ($keyword) {
                // 共通検索条件
                if (get_proc_data($keyword, "search_game_id"))        $sql_cmd = $sql_cmd->where('game_id', $keyword['search_game_id']);
                if (get_proc_data($keyword, "search_sprite_sheet_id")) $sql_cmd = $sql_cmd->where('sprite_sheet_id', $keyword['search_sprite_sheet_id']);
                if (get_proc_data($keyword, "search_item_key"))       $sql_cmd = $sql_cmd->where('item_key', $keyword['search_item_key']);
                if (get_proc_data($keyword, "search_type"))           $sql_cmd = $sql_cmd->where('type', $keyword['search_type']);

                //管理者による検索
                if (get_proc_data($keyword, "admin_flag")) {
                    // 追加の管理者用フィルタがあればここに
                //ユーザーによる検索
                } else {
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
     * 🌟 アイテムの削除
     */
    public static function delItem($id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $item = self::find($id);
            if (!$item) {
                return ['success' => false, 'msg' => '削除対象のアイテムが見つかりません。'];
            }

            $itemName = $item->name;
            $item->delete();

            return ['success' => true, 'msg' => "パーツ [{$itemName}] を削除しました。"];
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => '削除中にエラーが発生しました。'];
        }
    }

    /**
     * 🌟 アイテムデータの登録・更新処理をモデルに集約
     */
    public static function chgItem($data, $gameId)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $id              = get_proc_data($data, 'id');
            $item_key        = get_proc_data($data, 'item_key');
            $name            = get_proc_data($data, 'name');
            $type            = get_proc_data($data, 'type');
            $sprite_sheet_id = get_proc_data($data, 'sprite_sheet_id');
            $sort_order      = get_proc_data($data, 'sort_order') ?? 0;
            
            // 🌟 物理カラムから移行した値の処理
            $grid_w          = get_proc_data($data, 'grid_w') ?? 1;
            $grid_h          = get_proc_data($data, 'grid_h') ?? 1;
            $atlas_frame     = get_proc_data($data, 'atlas_frame');

            $custom_settings = get_proc_data($data, 'custom_settings') ?? [];
            if (empty($custom_settings) && !empty($data['custom_settings_json'])) {
                $custom_settings = json_decode($data['custom_settings_json'], true) ?? [];
            }
            
            // 🌟 移行データを custom_settings に統合
            $custom_settings['grid_w'] = $grid_w;
            $custom_settings['grid_h'] = $grid_h;
            if ($atlas_frame) $custom_settings['atlas_frame'] = $atlas_frame;

            $enable_flag     = (bool)(get_proc_data($data, 'enable_flag') ?? false);
            $login_user_flag = (bool)(get_proc_data($data, 'login_user_flag') ?? false);
            $admin_only_flag = (bool)(get_proc_data($data, 'admin_only_flag') ?? false);
            make_error_log($error_log, "chgItem called with data: " . json_encode($data));

            if (empty($id)) {
                // 同一ゲーム、同一スプライトシート内での item_key 重複チェック
                $exists = self::where('game_id', $gameId)
                    ->where('sprite_sheet_id', $sprite_sheet_id)
                    ->where('item_key', $item_key)
                    ->exists();
                if ($exists) {
                    return ['success' => false, 'msg' => "エラー：識別キー [{$item_key}] はこのシート内で既に登録されています。"];
                }

                $item = self::create([
                    'game_id'         => $gameId,
                    'item_key'        => $item_key,
                    'name'            => $name,
                    'type'            => $type,
                    'sprite_sheet_id' => $sprite_sheet_id,
                    'sort_order'      => $sort_order,
                    'custom_settings' => $custom_settings,
                    'enable_flag'     => $enable_flag,
                    'login_user_flag' => $login_user_flag,
                    'admin_only_flag' => $admin_only_flag,
                ]);

                return ['success' => true, 'msg' => "新規アイテム [{$name}] を追加しました！", 'item' => $item];

            } else {
                $item = self::findOrFail($id);
                $item->update([
                    'name'            => $name,
                    'type'            => $type,
                    'sprite_sheet_id' => $sprite_sheet_id,
                    'sort_order'      => $sort_order,
                    'custom_settings' => $custom_settings,
                    'enable_flag'     => $enable_flag,
                    'login_user_flag' => $login_user_flag,
                    'admin_only_flag' => $admin_only_flag,
                ]);

                return ['success' => true, 'msg' => "アイテム [{$item->name}] を更新しました。", 'item' => $item];
            }
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }
}