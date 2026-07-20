<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameUserData extends Model
{
    protected $table = 'game_user_data'; 
    protected $guarded = [];

    // JSONや日時のキャスト設定（バッチリです！）
    protected $casts = [
        'unlocked_features' => 'array',
        'custom_stats' => 'array',
        'last_stamina_updated_at' => 'datetime',
        'last_played_at' => 'datetime',
    ];

    // このデータを持つプレイヤー情報
    public function user() {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // 対象のゲーム情報
    public function game() {
        return $this->belongsTo(GameList::class, 'game_id');
    }
}