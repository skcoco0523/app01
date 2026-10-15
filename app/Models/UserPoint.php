<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPoint extends Model
{
    // テーブル名を明示（Laravelの規則である user_points ではなく user_point のため）
    protected $table = 'user_point';

    protected $fillable = [
        'user_id',
        'type',       // 'paid' or 'free'
        'point',      // 付与時の初期ポイント
        'point_rem',  // 未消費の残ポイント
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}