<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LifeTheater;
use App\Models\User;

class LifeTheaterMedia extends Model
{
    use HasFactory;

    protected $table = 'life_theater_media';

    protected $fillable = [
        'life_theater_id',
        'user_id',
        'name',
        'image_s3_key',
        'category',
    ];

    // リレーション: 親作品
    public function lifeTheater()
    {
        return $this->belongsTo(LifeTheater::class);
    }

    // リレーション: アップロードしたユーザー
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // =========================================================================
    // データ操作メソッド
    // =========================================================================

    public static function createMedia($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            if (!isset($data['life_theater_id']) || !isset($data['image_s3_key'])) {
                return ['id' => null, 'error_code' => 1];
            }

            $media = self::create($data);
            return ['id' => $media->id, 'media' => $media, 'error_code' => 0];
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];
        }
    }

    public static function delMedia($data)
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