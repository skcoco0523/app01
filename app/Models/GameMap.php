<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameMap extends Model
{
    use HasFactory;

    protected $table = 'game_maps';

    protected $fillable = [
        'game_id', 'map_key', 'name', 'custom_settings', 'thumbnail_url'
    ];

    protected $casts = [
        'custom_settings' => 'array',
    ];

    /**
     * 親ゲームへのリレーション
     */
    public function game()
    {
        return $this->belongsTo(GameList::class, 'game_id');
    }

    /**
     * このマップを使用しているステージ
     */
    public function stages()
    {
        return $this->hasMany(GameStage::class, 'map_id');
    }

    /**
     * マップ一覧の取得
     */
    public static function getMapList($disp_cnt = null, $pageing = false, $page = 1, $keyword = null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = self::query();
            if ($keyword) {
                if (get_proc_data($keyword, "admin_flag")) {
                    if (get_proc_data($keyword, "search_game_id")) {
                        $sql_cmd = $sql_cmd->where('game_id', $keyword['search_game_id']);
                    }
                    if (get_proc_data($keyword, "search_name")) {
                        $sql_cmd = $sql_cmd->where('name', 'like', '%' . $keyword['search_name'] . '%');
                    }
                }
            }
            $sql_cmd = $sql_cmd->orderBy('id', 'asc');

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
     * マップの保存
     */
    public static function chgMap($data, $gameId)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            if (empty($data['id'])) {
                $exists = self::where('game_id', $gameId)
                    ->where('map_key', $data['map_key'])
                    ->exists();

                if ($exists) {
                    return ['success' => false, 'msg' => "エラー：マップ識別キー [{$data['map_key']}] は既にこのゲームに登録されています。"];
                }

                $map = self::create([
                    'game_id'         => $gameId,
                    'map_key'         => $data['map_key'],
                    'name'            => $data['name'],
                    'custom_settings' => json_decode($data['custom_settings_json'] ?? '{}', true) ?? [],
                    'thumbnail_url'   => $data['thumbnail_url'] ?? null,
                ]);

                return ['success' => true, 'msg' => "新規マップ [{$data['name']}] を追加しました！", 'map' => $map];
            } else {
                $map = self::findOrFail($data['id']);
                
                if ($map->map_key !== $data['map_key']) {
                    $exists = self::where('game_id', $gameId)->where('map_key', $data['map_key'])->exists();
                    if ($exists) {
                        return ['success' => false, 'msg' => "エラー：変更先のマップ識別キー [{$data['map_key']}] は既に使用されています。"];
                    }
                }

                $map->update([
                    'map_key'         => $data['map_key'],
                    'name'            => $data['name'],
                    'custom_settings' => json_decode($data['custom_settings_json'] ?? '{}', true) ?? [],
                    'thumbnail_url'   => $data['thumbnail_url'] ?? null,
                ]);

                return ['success' => true, 'msg' => "マップ [{$map->name}] を更新しました。", 'map' => $map];
            }
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }
}
