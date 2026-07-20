<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GameCharacter extends Model
{
    use HasFactory;

    protected $table = 'game_characters';
    protected $fillable = ['game_id', 'character_key', 'name', 'type', 'sort_order', 'enable_flag', 'login_user_flag', 'admin_only_flag', 'motion_data'];

    // キャスト設定（JSONカラムを自動で配列として扱う）
    protected $casts = [
        'motion_data' => 'array',
    ];

    // 親ゲームへのリレーション
    public function game() { return $this->belongsTo(GameList::class, 'game_id'); }

    public static function getCharacterList($disp_cnt=null, $pageing=false, $page=1, $keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = self::query();
            if ($keyword) {
                //管理者による検索
                if(get_proc_data($keyword,"admin_flag")){
                    if (get_proc_data($keyword, "search_enable_flag"))     $sql_cmd = $sql_cmd->where('enable_flag', $keyword['search_enable_flag']);
                    if (get_proc_data($keyword, "search_login_user_flag")) $sql_cmd = $sql_cmd->where('login_user_flag', $keyword['search_login_user_flag']);
                    if (get_proc_data($keyword, "search_admin_only_flag")) $sql_cmd = $sql_cmd->where('admin_only_flag', $keyword['search_admin_only_flag']);
                    if (get_proc_data($keyword, "search_game_id"))         $sql_cmd = $sql_cmd->where('game_id', $keyword['search_game_id']);
                    if (get_proc_data($keyword, "search_name"))            $sql_cmd = $sql_cmd->where('name', 'like', '%' . $keyword['search_name'] . '%');
                    if (get_proc_data($keyword, "search_type"))            $sql_cmd = $sql_cmd->where('type', $keyword['search_type']);

                //ユーザーによる検索
                }else{      
                    $sql_cmd = $sql_cmd->where('enable_flag', true);    //公開中のみ
                    if(!Auth::user()){
                        $sql_cmd = $sql_cmd->where('login_user_flag', false);   //ログインユーザー限定を除外
                        $sql_cmd = $sql_cmd->where('admin_only_flag', false);   //管理者専用を除外
                    }elseif(!Auth::user()->admin_flag) {
                        $sql_cmd = $sql_cmd->where('admin_only_flag', false);   //管理者専用を除外
                    }
                    if (get_proc_data($keyword, "search_game_id")) $sql_cmd = $sql_cmd->where('game_id', $keyword['search_game_id']);
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
     * 新規キャラクターの登録処理専用
     */
    public static function createCharacter($data, $gameId)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            // 重複ガード
            $exists = self::where('game_id', $gameId)
                ->where('character_key', $data['character_key'])
                ->exists();

            if ($exists) {
                return ['success' => false, 'msg' => 'エラー：入力された識別キーは既にこのゲームに登録されています。'];
            }

            // 新しい「つまみ食い合成アセット仕様」に完全準拠した、初期物理セットアップ構造を定義
            // 🌟 親ゲームの view_mode を取得して、初期の forms 構造を動的に切り替える！
            $game = GameList::find($gameId);
            $viewMode = $game ? $game->view_mode : 'side_view';

            if ($viewMode === 'side_view') {
                $initialForms = ['right' => (object)[], 'left' => (object)[]];
            } elseif ($viewMode === 'top_down') {
                $initialForms = ['front' => (object)[], 'back' => (object)[], 'side' => (object)[]];
            } else {
                $initialForms = ['default' => (object)[]];
            }

            // 新しい「つまみ食い合成アセット仕様」に完全準拠した、初期物理セットアップ構造を定義
            $defaultMotion = [
                'physics' => [
                    'default' => [
                        'hitboxWidth' => 118,
                        'hitboxHeight' => 326,
                        'footY' => 90,
                        'offsetX' => 0,
                        'globalPartScale' => 0.8
                    ]
                ],
                'setup' => ['parts' => []],
                'forms' => $initialForms, // 🌟 視点に最適化された器を注入
                'animations' => (object)[]
            ];

            $character = self::create([
                'game_id'         => $gameId,
                'character_key'   => $data['character_key'],
                'name'            => $data['name'],
                'type'            => $data['type'] ?? 'player',
                'sort_order'      => $data['sort_order'] ?? 0,
                'enable_flag'     => $data['enable_flag'] ?? false,
                'login_user_flag' => $data['login_user_flag'] ?? false,
                'admin_only_flag' => $data['admin_only_flag'] ?? false,
                'motion_data'     => $defaultMotion, // 🌟 魔法の司令塔JSONの初期枠を注入
            ]);

            return ['success' => true, 'msg' => "新規キャラクター [{$character->name}] を目録に登録しました！"];

        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }

    /**
     * 既存キャラクターのプロフィール更新処理専用
     */
    public static function chgCharacter($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $character = self::findOrFail($data['id']);
            
            // 🌟 既存の motion_data は絶対に破壊せず、プロフィール系のみを上書き更新します
            $character->update([
                'name'            => $data['name'],
                'type'            => $data['type'] ?? 'player',
                'sort_order'      => $data['sort_order'] ?? 0,
                'enable_flag'     => $data['enable_flag'] ?? false,
                'login_user_flag' => $data['login_user_flag'] ?? false,
                'admin_only_flag' => $data['admin_only_flag'] ?? false,
            ]);

            return ['success' => true, 'msg' => "キャラクター [{$character->name}] のプロフィールを更新しました。"];

        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => 'システムエラーが発生しました。: ' . $e->getMessage()];
        }
    }
}