<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameUserItem extends Model
{
    protected $guarded = [];

    // このアイテムを持っているプレイヤー情報
    public function user() {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    // 対象のゲーム情報
    public function game() {
        return $this->belongsTo(GameList::class, 'game_id');
    }

    // 拾ったアイテムの「マスター情報（名前や見た目のアトラス設定など）」を引っ張る
    public function masterItem() {
        return $this->belongsTo(GameItem::class, 'game_item_id');
    }
}