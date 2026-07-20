<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_items', function (Blueprint $table) {
            $table->id();
            // 🌟 もちろん「ゲームのID」に紐付けます
            $table->foreignId('game_id')->constrained('game_list')->onDelete('cascade');
            
            $table->string('item_key');           // 'coin', 'potion', 'key_stage1' などの識別キー
            $table->string('name');               // アイテム名（例: 黄金のコイン、謎の回復薬）
            $table->string('type')->index();      // 'consumable'（消費アイテム）, 'collectible'（収集品）など
            
            // 🌟 アクセス制御フラグ
            $table->boolean('enable_flag')->default(false);       // falseでメンテナンス（非公開）
            $table->boolean('login_user_flag')->default(false);  // trueでログイン必須
            $table->boolean('admin_only_flag')->default(false);  // trueで開発中（管理者のみテスト可能）
            
            // 🌟 ゲームごとのアイテム効果をJSONで丸投げ吸収！
            // ツインフェイサーなら： {"score_bonus": 300, "heal_hp": 0}
            // RPGなら： {"heal_hp": 50, "price": 100, "limit": 99}
            // アクションなら： {"is_key": true, "target_door": "gate_a"}
            $table->json('custom_settings'); 

            $table->integer('sort_order')->default(0); // バッグの中身やショップでの並び順
            $table->timestamps();
            
            // 同じゲーム内で item_key が重複しないようにユニーク制限
            $table->unique(['game_id', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_items');
    }
};