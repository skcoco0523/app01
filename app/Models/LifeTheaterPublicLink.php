<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LifeTheaterPublicLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'life_theater_id',
        'user_id',
        'token',
        'memo',
        'is_ad_free',
        'view_count',
        'password',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_ad_free' => 'boolean',
        'is_active'  => 'boolean',
        'expires_at' => 'datetime',
    ];

    // リレーション: 対象の作品
    public function lifeTheater()
    {
        return $this->belongsTo(LifeTheater::class, 'life_theater_id');
    }

    // リレーション: 発行者
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 重複しないユニークなトークンを生成
     */
    public static function generateUniqueToken()
    {
        do {
            $token = Str::random(32); // 32文字のランダム文字列
        } while (self::where('token', $token)->exists());

        return $token;
    }
}