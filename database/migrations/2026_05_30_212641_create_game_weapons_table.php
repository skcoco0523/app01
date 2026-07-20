<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_weapons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('game_list')->onDelete('cascade');
            
            $table->string('weapon_key');         // 'arrow', 'magic_ball' などの識別キー
            $table->string('name');               // 武器名（例: 紫電の弓）
            $table->string('type');               // 'projectile'(遠距離), 'melee'(近接)
            
            // 🌟 アクセス制御フラグ
            $table->boolean('enable_flag')->default(false);       // falseでメンテナンス（非公開）
            $table->boolean('login_user_flag')->default(false);  // trueでログイン必須
            $table->boolean('admin_only_flag')->default(false);  // trueで開発中（管理者のみテスト可能）
            
            // 🌟 武器ごとのスペックをJSONで吸収
            // {"speed_x": 750, "lifespan": 1000, "width": 35, "height": 12} など
            $table->json('custom_settings'); 

            $table->integer('sort_order')->default(0); 

            $table->timestamps();
            $table->unique(['game_id', 'weapon_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_weapons');
    }
};