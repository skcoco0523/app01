<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no',
        'user_id',
        'provider',
        'external_order_id',
        'external_payment_id',
        'config_name',
        'amount',
        'points',
        'valid_days',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array', // JSONカラムを自動的に配列に変換
    ];

    /**
     * この注文をしたユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この注文によって付与されたポイント履歴を取得
     */
    public function userPoints()
    {
        return $this->hasMany(UserPoint::class, 'point_order_id');
    }
}