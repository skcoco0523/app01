<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LifeTheaterSlide;
use App\Models\LifeTheaterMedia;

class LifeTheaterSlideObject extends Model
{
    use HasFactory;

    /**
     * オブジェクト（キャスト・吹き出し）個別設定項目の定義
     */
    public static function getConfigDefinitions(): array
    {
        return [
            'pop_delay' => [
                'label'       => '表示遅延時間',
                'description' => 'スライド表示後に吹き出しが立ち上がるまでの秒数です。',
                'type'        => 'select',
                'options'     => [0 => '即時', 300 => '0.3秒', 500 => '0.5秒', 1000 => '1.0秒', 1500 => '1.5秒', 2000 => '2.0秒'],
                'default'     => 500,
                'premium'     => false,
            ],
            'balloon_position' => [
                'label'       => '吹き出し展開位置',
                'description' => 'アイコンに対する吹き出しの配置位置を設定します。',
                'type'        => 'select',
                'options'     => ['auto' => '自動配置', 'right' => '右側に展開', 'left' => '左側に展開', 'top' => '上部に展開'],
                'default'     => 'auto',
                'premium'     => false,
            ],
            'pop_duration' => [
                'label'       => '表示保持時間',
                'description' => '吹き出しが表示・強調される持続時間です。',
                'type'        => 'select',
                'options'     => [1500 => '1.5秒', 2000 => '2.0秒', 2500 => '2.5秒', 3000 => '3.0秒', 4000 => '4.0秒', 5000 => '5.0秒'],
                'default'     => 2500,
                'premium'     => true,
            ],
            'animation_style' => [
                'label'       => 'アニメーション種別',
                'description' => '登場時のアニメーション効果を設定します。',
                'type'        => 'select',
                'options'     => ['pop' => 'ポワンと拡大', 'float' => 'ふわっと浮上', 'bounce' => 'バウンス'],
                'default'     => 'pop',
                'premium'     => true,
            ],
            'emphasis_scale' => [
                'label'       => '強調時拡大倍率',
                'description' => '発言強調時のアイコン拡大率を設定します。',
                'type'        => 'select',
                'options'     => ['1.0' => '拡大なし', '1.1' => '1.1倍', '1.2' => '1.2倍', '1.25' => '1.25倍', '1.5' => '1.5倍', '1.75' => '1.75倍', '2.0' => '2.0倍', '2.5' => '2.5倍'],
                'default'     => '1.25',
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
     * rawな config_data（文字列 or 配列 or null）をパースしてデフォルト値と合成する（二重エンコード対策含む）
     */
    public static function parseConfig($rawConfigData): array
    {
        $defaults   = self::getDefaultConfig();
        $configData = $rawConfigData;

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

    protected $table = 'life_theater_slide_objects';

    protected $fillable = [
        'life_theater_slide_id',
        'life_theater_media_id',
        'type',
        'name',
        'text',
        'sort_order',
        'config_data',
    ];

    protected $casts = [
        'config_data' => 'array',
    ];

    // リレーション: 親スライド
    public function slide()
    {
        return $this->belongsTo(LifeTheaterSlide::class, 'life_theater_slide_id');
    }

    // リレーション: 使用画像メディア
    public function media()
    {
        return $this->belongsTo(LifeTheaterMedia::class, 'life_theater_media_id');
    }

    // =========================================================================
    // データ操作メソッド
    // =========================================================================

    public static function createObject($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            if (!isset($data['life_theater_slide_id'])) {
                return ['id' => null, 'error_code' => 1];
            }

            // config_data が未指定の場合はデフォルト値をセット
            if (!isset($data['config_data'])) {
                $data['config_data'] = self::getDefaultConfig();
            } elseif (is_array($data['config_data'])) {
                $data['config_data'] = self::parseConfig($data['config_data']);
            }

            $object = self::create($data);
            return ['id' => $object->id, 'error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];
        }
    }

    public static function chgObject($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $object = self::find($data['id'] ?? null);
            if (!$object) {
                return ['id' => null, 'error_code' => -1];
            }

            if (array_key_exists('life_theater_media_id', $data)) $object->life_theater_media_id = $data['life_theater_media_id'];
            if (isset($data['type']))                           $object->type                 = $data['type'];
            if (isset($data['name']))                           $object->name                 = $data['name'];
            if (isset($data['text']))                           $object->text                 = $data['text'];
            if (isset($data['sort_order']))                     $object->sort_order           = $data['sort_order'];
            if (isset($data['config_data']))                    $object->config_data          = $data['config_data'];

            // Eloquentの $casts = ['config_data' => 'array'] が正しく動作するよう save() で更新
            $object->save();

            return ['error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['error_code' => -1];
        }
    }

    public static function delObject($data)
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