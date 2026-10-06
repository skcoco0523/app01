<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LifeTheaterSlide;
use App\Models\LifeTheaterMedia;

class LifeTheaterSlideObject extends Model
{
    use HasFactory;

    protected $table = 'life_theater_slide_objects';

    protected $fillable = [
        'life_theater_slide_id',
        'life_theater_media_id',
        'type',
        'name',
        'text',
        'sort_order',
        'object_config',
    ];

    protected $casts = [
        'object_config' => 'array',
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
            $updateData = [];
            if (array_key_exists('life_theater_media_id', $data)) $updateData['life_theater_media_id'] = $data['life_theater_media_id'];
            if (isset($data['type']))                            $updateData['type']                  = $data['type'];
            if (isset($data['name']))                            $updateData['name']                  = $data['name'];
            if (isset($data['text']))                            $updateData['text']                  = $data['text'];
            if (isset($data['sort_order']))                      $updateData['sort_order']            = $data['sort_order'];
            if (isset($data['object_config']))                   $updateData['object_config']         = $data['object_config'];

            self::where('id', $data['id'])->update($updateData);
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