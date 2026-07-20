<?php

namespace App\Models;

use App\Http\Middleware\Authenticate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GameList extends Model
{
    use HasFactory;

    protected $table = 'game_list';
    protected $fillable = ['game_key', 'title', 'description', 'version', 'view_mode', 'orientation', 'enable_flag', 'login_user_flag', 'admin_only_flag'];

    // リレーション定義（子アセット）
    public function characters() { return $this->hasMany(GameCharacter::class, 'game_id'); }
    public function maps()       { return $this->hasMany(GameMap::class, 'game_id'); }
    public function stages()     { return $this->hasMany(GameStage::class, 'game_id'); }
    public function weapons()    { return $this->hasMany(GameWeapon::class, 'game_id'); }
    public function items()      { return $this->hasMany(GameItem::class, 'game_id'); }

    public static function getGameList($disp_cnt=null, $pageing=false, $page=1, $keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = self::query();
            if ($keyword) {
                //管理者による検索
                if (get_proc_data($keyword, "admin_flag")) {
                    if (get_proc_data($keyword, "search_game_key")) 
                        $sql_cmd = $sql_cmd->where('game_key', $keyword['search_game_key']);
                    
                    if (get_proc_data($keyword, "search_keyword")) {
                        $sql_cmd = $sql_cmd->where(function($q) use ($keyword) {
                            $q->where('title', 'like', '%' . $keyword['search_keyword'] . '%')
                              ->orWhere('description', 'like', '%' . $keyword['search_keyword'] . '%');
                        });
                    }
                //ユーザーによる検索
                } else {
                    $sql_cmd = $sql_cmd->where('enable_flag', true);    //公開中のみ
                    if(!Auth::user()){
                        $sql_cmd = $sql_cmd->where('login_user_flag', false);   //ログインユーザー限定を除外
                        $sql_cmd = $sql_cmd->where('admin_only_flag', false);   //管理者専用を除外
                    }elseif(!Auth::user()->admin_flag) {
                        $sql_cmd = $sql_cmd->where('admin_only_flag', false);   //管理者専用を除外
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
     * 🌟 ゲーム作品の登録・更新処理をモデルに集約
     */
    public static function chgGame($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            // IDが空なら「新規登録フェーズ」
            if (empty($data['id'])) {
                // キーの一意性防衛チェック
                $exists = self::where('game_key', $data['game_key'])->exists();
                if ($exists) {
                    return ['success' => false, 'msg' => 'エラー：その識別キー(game_key)は既に他の作品で使用されています。', 'game' => null];
                }

                $game = self::create([
                    'game_key'        => $data['game_key'],
                    'title'           => $data['title'],
                    'description'     => $data['description'] ?? null,
                    'version'         => $data['version'] ?? 1,
                    'view_mode'       => $data['view_mode'] ?? 'side_view', // 🌟 新規登録時に保存
                    'orientation'     => $data['orientation'] ?? 'landscape',
                    'enable_flag'     => $data['enable_flag'] ?? false,
                    'login_user_flag' => $data['login_user_flag'] ?? false,
                    'admin_only_flag' => $data['admin_only_flag'] ?? false,
                ]);

                return ['success' => true, 'msg' => "新規ゲーム作品 [{$game->title}] を登録しました！", 'game' => $game];

            } else {
                // IDがあれば「既存編集フェーズ」
                $game = self::findOrFail($data['id']);
                $game->update([
                    'title'           => $data['title'],
                    'description'     => $data['description'] ?? null,
                    'version'         => $data['version'] ?? 1,
                    'view_mode'       => $data['view_mode'] ?? 'side_view', // 🌟 更新（既存編集）時に反映
                    'orientation'     => $data['orientation'] ?? 'landscape',
                    'enable_flag'     => $data['enable_flag'] ?? false,
                    'login_user_flag' => $data['login_user_flag'] ?? false,
                    'admin_only_flag' => $data['admin_only_flag'] ?? false,
                ]);

                return ['success' => true, 'msg' => "ゲーム [{$game->title}] の基本設定を更新しました。", 'game' => $game];
            }

        } catch (\Exception $e) {
            // UserRequestの流儀に合わせたエラーログ＆失敗の返却
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage(), 'game' => null];
        }
    }
    /**
     * 🌟 ゲーム作品の削除処理をモデルに集約
     */
    public static function delGame($id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $game = self::findOrFail($id);
            $title = $game->title;
            
            // 削除実行（cascade制約により、紐づくキャラ・ステージ・武器・アイテムも自動連鎖削除されます）
            $game->delete();

            return ['success' => true, 'msg' => "ゲーム作品 [{$title}] および、関連するすべてのマスターデータを完全に削除しました。"];

        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => '削除システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }
}
