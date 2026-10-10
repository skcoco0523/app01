<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LifeTheaterSlide extends Model
{
    use HasFactory;

    /**
     * スライド個別設定の定義
     */
    public static function getConfigDefinitions(): array
    {
        return [
            'transition_type' => [
                'label'       => '切り替えアニメーション',
                'description' => '次のへ切り替わる際のエフェクトを設定します。',
                'type'        => 'select',
                'options'     => ['fade' => 'フェードイン', 'slide' => 'スライドイン', 'zoom' => 'ズームイン', 'none' => '切り替えなし'],
                'default'     => 'fade',
                'premium'     => false,
            ],
            'text_position' => [
                'label'       => 'メッセージ表示位置',
                'description' => 'テキストメッセージの配置位置を設定します。',
                'type'        => 'select',
                'options'     => ['center' => '中央表示', 'bottom' => '下部表示', 'top' => '上部表示'],
                'default'     => 'center',
                'premium'     => false,
            ],
            'duration_override' => [
                'label'       => 'スライド表示時間',
                'description' => 'このスライドの表示時間を個別指定します（未指定時は全体設定）。',
                'type'        => 'select',
                'options'     => [
                    '' => '作品全体の設定を継承', 
                    '2000' => '2秒', '3000' => '3秒', '5000' => '5秒', '7000' => '7秒', '8000' => '8秒',
                    '10000' => '10秒','12000' => '12秒', '15000' => '15秒', '20000' => '20秒',
                    '30000' => '30秒', '40000' => '40秒', '50000' => '50秒', '60000' => '60秒',
                    '90000' => '90秒', '120000' => '120秒', '150000' => '150秒', '180000' => '180秒'
                    ],
                'default'     => '',
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
     * 二重エンコードされた文字列でも確実に配列へパースする
     */
    public static function parseConfig($rawConfigData): array
    {
        $defaults = self::getDefaultConfig();
        $configData = $rawConfigData;

        // 文字列のうちは配列になるまでデコードを繰り返す（二重エンコード対策）
        while (is_string($configData) && $configData !== '') {
            $decoded = json_decode($configData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                break;
            }
            $configData = $decoded;
        }

        if (!is_array($configData)) {
            $configData = [];
        }

        return array_merge($defaults, $configData);
    }

    /**
     * プランに応じた設定値の補正
     */
    public static function filterConfigByPlan(array $inputConfig, bool $isPremium, array $currentConfig = []): array
    {
        $filtered = [];
        foreach (self::getConfigDefinitions() as $key => $def) {
            if (($def['premium'] ?? false) && !$isPremium) {
                $filtered[$key] = $currentConfig[$key] ?? $def['default'];
            } else {
                $filtered[$key] = $inputConfig[$key] ?? $currentConfig[$key] ?? $def['default'];
            }
        }
        return $filtered;
    }

    protected $fillable = [
        'life_theater_id',
        'step_order',
        'label',
        'line',
        'title',
        'subtitle',
        'content',
        'slide_date',
        'life_theater_media_id',
        'config_data',
    ];

    protected $casts = [
        'config_data' => 'array',
    ];

    public function lifeTheater()
    {
        return $this->belongsTo(LifeTheater::class);
    }

    public function media()
    {
        return $this->belongsTo(LifeTheaterMedia::class, 'life_theater_media_id');
    }

    public function objects()
    {
        return $this->hasMany(LifeTheaterSlideObject::class, 'life_theater_slide_id')->orderBy('sort_order', 'asc');
    }

    public static function getSlideList($life_theater_id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            return self::where('life_theater_id', $life_theater_id)
                ->with(['media', 'objects.media'])
                ->orderBy('step_order', 'asc')
                ->get();
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return [];
        }
    }

    public static function createSlide($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            if (!isset($data['life_theater_id'])) return ['id' => null, 'error_code' => 1];

            if (!isset($data['step_order'])) {
                $maxOrder = self::where('life_theater_id', $data['life_theater_id'])->max('step_order');
                $data['step_order'] = is_null($maxOrder) ? 0 : $maxOrder + 1;
            }

            if (!isset($data['config_data'])) {
                $data['config_data'] = self::getDefaultConfig();
            }

            $request = self::create($data);
            return ['id' => $request->id, 'error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];
        }
    }

    public static function chgSlide($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $slide = self::find($data['id'] ?? null);
            if (!$slide) {
                return ['id' => null, 'error_code' => -1];
            }

            if (isset($data['step_order']))                       $slide->step_order            = $data['step_order'];
            if (isset($data['label']))                            $slide->label                 = $data['label'];
            if (isset($data['line']))                             $slide->line                  = $data['line'];
            if (isset($data['title']))                            $slide->title                 = $data['title'];
            if (isset($data['subtitle']))                         $slide->subtitle              = $data['subtitle'];
            if (isset($data['content']))                          $slide->content               = $data['content'];
            if (isset($data['slide_date']))                       $slide->slide_date            = $data['slide_date'];
            if (array_key_exists('life_theater_media_id', $data)) $slide->life_theater_media_id = $data['life_theater_media_id'];
            if (isset($data['config_data']))                      $slide->config_data           = $data['config_data'];

            $slide->save(); // Eloquentモデル経由でキャストを正常に動作させて保存

            return ['error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['error_code' => -1];
        }
    }

    public static function delSlide($data)
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