<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LifeTheater;
use App\Models\LifeTheaterMedia;
use App\Models\LifeTheaterSlideObject;

class LifeTheaterSlide extends Model
{
    use HasFactory;
    public const DEFAULT_CONFIG = [
        'duration_override' => null,     // このコマ専用の表示時間 (null時は全体設定を継承)
        'transition_type'   => 'fade',    // 次のコマへの切り替え効果 (fade / slide / zoom / none)
        'text_position'     => 'center',  // メッセージテキスト配置 (top / center / bottom)
        'bgm_action'        => 'continue',// このコマでのBGM挙動 (continue / fade_out / stop)
    ];

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

    // リレーション: 親作品
    public function lifeTheater()
    {
        return $this->belongsTo(LifeTheater::class);
    }

    // リレーション: メイン画像
    public function media()
    {
        return $this->belongsTo(LifeTheaterMedia::class, 'life_theater_media_id');
    }

    // リレーション: スライド上のオブジェクト（キャスト・吹き出し等）
    public function objects()
    {
        return $this->hasMany(LifeTheaterSlideObject::class, 'life_theater_slide_id')->orderBy('sort_order', 'asc');
    }

    // =========================================================================
    // データ操作メソッド
    // =========================================================================

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
        make_error_log($error_log, "-------start-------");
        try {
            $error_code = 0;
            if (!isset($data['life_theater_id'])) $error_code = 1;

            if ($error_code) {
                make_error_log($error_log, "error_code=" . $error_code);
                return ['id' => null, 'error_code' => $error_code];
            }

            if (!isset($data['step_order'])) {
                $maxOrder = self::where('life_theater_id', $data['life_theater_id'])->max('step_order');
                $data['step_order'] = is_null($maxOrder) ? 0 : $maxOrder + 1;
            }

            $request = self::create($data);
            make_error_log($error_log, "success id=" . $request->id);
            return ['id' => $request->id, 'error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];
        }
    }

    public static function chgSlide($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $slide = self::where('id', $data['id'])->first();
            if (!$slide) {
                make_error_log($error_log, ".not found id:" . $data['id']);
                return ['id' => null, 'error_code' => -1];
            }

            $updateData = [];
            if (isset($data['step_order']))                       $updateData['step_order']            = $data['step_order'];
            if (isset($data['label']))                            $updateData['label']                 = $data['label'];
            if (isset($data['line']))                             $updateData['line']                  = $data['line'];
            if (isset($data['title']))                            $updateData['title']                 = $data['title'];
            if (isset($data['subtitle']))                         $updateData['subtitle']              = $data['subtitle'];
            if (isset($data['content']))                          $updateData['content']               = $data['content'];
            if (isset($data['slide_date']))                       $updateData['slide_date']            = $data['slide_date'];
            if (array_key_exists('life_theater_media_id', $data)) $updateData['life_theater_media_id'] = $data['life_theater_media_id'];
            if (isset($data['config_data']))                      $updateData['config_data']           = $data['config_data'];

            self::where('id', $data['id'])->update($updateData);

            make_error_log($error_log, "success");
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