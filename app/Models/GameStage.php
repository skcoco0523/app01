<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GameStage extends Model
{
    use HasFactory;

    protected $table = 'game_stages';
    
    protected $fillable = [
        'game_id', 'map_id', 'type', 'number', 'name', 'custom_settings', 
        'enable_flag', 'login_user_flag', 'admin_only_flag'
    ];

    // JSONデータを自動的に配列へ変換
    protected $casts = [
        'custom_settings' => 'array',
    ];

    // 親ゲームへのリレーション
    public function game() {
        return $this->belongsTo(GameList::class, 'game_id');
    }

    // マップへのリレーション
    public function map() {
        return $this->belongsTo(GameMap::class, 'map_id');
    }

    public static function getStageList($disp_cnt=null, $pageing=false, $page=1, $keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = self::query();
            if ($keyword) {
                //管理者による検索
                if (get_proc_data($keyword, "admin_flag")) {
                    if (get_proc_data($keyword, "search_game_id"))      $sql_cmd = $sql_cmd->where('game_id', $keyword['search_game_id']);
                    if (get_proc_data($keyword, "search_number")) $sql_cmd = $sql_cmd->where('number', $keyword['search_number']);
                //ユーザーによる検索
                } else {
                    if (get_proc_data($keyword, "search_game_id")) $sql_cmd = $sql_cmd->where('game_id', $keyword['search_game_id']);
                    $sql_cmd = $sql_cmd->where('enable_flag', true);
                }
            }
            $sql_cmd = $sql_cmd->orderBy('number', 'asc');

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
     * 🌟 ステージデータの登録・更新処理をモデルに集約
     */
    public static function chgStage($data, $gameId)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            // IDが空なら「新規登録フェーズ」
            if (empty($data['id'])) {
                // 同じゲーム内でステージ番号が重複しないようにガード
                $exists = self::where('game_id', $gameId)
                    ->where('number', $data['number'])
                    ->exists();

                if ($exists) {
                    return ['success' => false, 'msg' => "エラー：ステージ番号 [{$data['number']}] は既にこのゲームに登録されています。"];
                }

                self::create([
                    'game_id'         => $gameId,
                    'map_id'          => $data['map_id'] ?? null,
                    'type'            => $data['type'] ?? 'fixed',
                    'number'          => $data['number'],
                    'name'            => $data['name'],
                    'custom_settings' => json_decode($data['custom_settings_json'] ?? '{}', true) ?? [],
                    'enable_flag'     => $data['enable_flag'] ?? false,
                    'login_user_flag' => $data['login_user_flag'] ?? false,
                    'admin_only_flag' => $data['admin_only_flag'] ?? false,
                ]);

                return ['success' => true, 'msg' => "新規ステージ [{$data['name']}] を追加しました！"];

            } else {
                // IDがあれば「既存編集フェーズ」
                $stage = self::findOrFail($data['id']);
                
                // 編集時も、もしステージ番号を変えようとした場合は重複チェック
                if ((int)$stage->number !== (int)$data['number']) {
                    $exists = self::where('game_id', $gameId)->where('number', $data['number'])->exists();
                    if ($exists) {
                        return ['success' => false, 'msg' => "エラー：変更先のステージ番号 [{$data['number']}] は既に使用されています。"];
                    }
                }

                $stage->update([
                    'map_id'          => $data['map_id'] ?? null,
                    'type'            => $data['type'] ?? 'fixed',
                    'number'          => $data['number'],
                    'name'            => $data['name'],
                    'custom_settings' => json_decode($data['custom_settings_json'] ?? '{}', true) ?? [],
                    'enable_flag'     => $data['enable_flag'] ?? false,
                    'login_user_flag' => $data['login_user_flag'] ?? false,
                    'admin_only_flag' => $data['admin_only_flag'] ?? false,
                ]);

                return ['success' => true, 'msg' => "ステージ [{$stage->name}] を更新しました。"];
            }

        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }
}