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

    /**
     * 設定項目の定義（ラベル・説明文・選択肢・初期値・プレミアム制限）
     */
    public static function getConfigDefinitions(): array
    {
        return [
            'title_size' => [
                'label'       => 'タイトル文字サイズ',
                'description' => 'スライドタイトルの表示サイズを設定します。',
                'type'        => 'select',
                'options'     => ['sm' => '控えめ (小)', 'md' => '標準 (中)', 'lg' => '強調 (大)'],
                'default'     => 'md',
                'premium'     => false,
            ],
            'subtitle_size' => [
                'label'       => 'サブタイトル文字サイズ',
                'description' => 'サブタイトルの表示サイズを設定します。',
                'type'        => 'select',
                'options'     => ['sm' => '控えめ (小)', 'md' => '標準 (中)', 'lg' => '強調 (大)'],
                'default'     => 'md',
                'premium'     => false,
            ],
            'slide_duration' => [
                'label'       => 'スライド自動切り替え速度',
                'description' => 'AUTO再生時に次のコマへ進む間隔です。',
                'type'        => 'select',
                'options'     => [
                    2000 => '2秒', 3000 => '3秒', 5000 => '5秒', 7000 => '7秒', 8000 => '8秒',
                    10000 => '10秒', 12000 => '12秒', 15000 => '15秒', 20000 => '20秒',
                    30000 => '30秒', 40000 => '40秒', 50000 => '50秒', 60000 => '60秒',
                ],
                'default'     => 7000,
                'premium'     => true,
            ],
            'show_brand_badge' => [
                'label'       => 'ブランドバッジの表示',
                'description' => '画面左上の「✨ Life Theater」ロゴを表示するか設定します。',
                'type'        => 'select',
                'options'     => [1 => '表示する', 0 => '非表示 (オリジナル作品化)'],
                'default'     => 1,
                'premium'     => true,
            ],
            'auto_loop' => [
                'label'       => '自動ループ再生',
                'description' => '最後のコマまで再生した後に最初に戻って繰り返し再生します。',
                'type'        => 'select',
                'options'     => [0 => '1回で停止', 1 => '永久ループ'],
                'default'     => 0,
                'premium'     => true,
            ],
            'particle_effect' => [
                'label'       => '背景アニメーション効果',
                'description' => '再生中の画面全体に舞い散る演出を追加します。',
                'type'        => 'select',
                'options'     => ['none' => 'なし', 'sparkle' => 'キラキラ', 'sakura' => '桜吹雪', 'snow' => '雪'],
                'default'     => 'none',
                'premium'     => true,
            ],
        ];
    }

    /**
     * デフォルト設定値のみを抽出して取得
     */
    public static function getDefaultConfig(): array
    {
        $defaults = [];
        foreach (self::getConfigDefinitions() as $key => $def) {
            $defaults[$key] = $def['default'];
        }
        return $defaults;
    }

    /**
     * rawな config_data（文字列 or 配列 or null）をパースしてデフォルト値と合成する
     */
    public static function parseConfig($rawConfigData): array
    {
        $saved = is_array($rawConfigData) 
            ? $rawConfigData 
            : json_decode($rawConfigData ?? '[]', true);

        return array_merge(self::getDefaultConfig(), $saved ?? []);
    }

    /**
     * プランに応じた設定値の補正（disabled項目の欠損防止・保護）
     */
    public static function filterConfigByPlan(array $inputConfig, bool $isPremium, array $currentConfig = []): array
    {
        $filtered = [];
        foreach (self::getConfigDefinitions() as $key => $def) {
            if (($def['premium'] ?? false) && !$isPremium) {
                // 非プレミアムの場合、プレミアム項目は既存値（無ければデフォルト値）を強制固定
                $filtered[$key] = $currentConfig[$key] ?? $def['default'];
            } else {
                // 許可されている項目は送信値（送信が無ければ既存値／デフォルト値）を採用
                $filtered[$key] = $inputConfig[$key] ?? $currentConfig[$key] ?? $def['default'];
            }
        }
        return $filtered;
    }

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
        'config_data'    => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function slides()
    {
        return $this->hasMany(LifeTheaterSlide::class)->orderBy('step_order', 'asc');
    }

    public function shares()
    {
        return $this->hasMany(LifeTheaterShare::class);
    }

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

            if (!isset($data['config_data'])) {
                $data['config_data'] = self::getDefaultConfig();
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