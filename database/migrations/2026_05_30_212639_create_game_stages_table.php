<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('game_list')->onDelete('cascade');
            
            $table->string('type')->default('fixed')
                ->comment('fixed:画面固定 horizontal:横スク horizontal_loop:横無限 vertical:縦スク vertical_loop:縦無限 free:全方位');
            $table->integer('number');      // ステージ1, ステージ2...
            $table->string('name');         // ステージ名（例: 始まりの奈落）
            
            // 🌟 アクセス制御フラグ
            $table->boolean('enable_flag')->default(false);       // falseでメンテナンス（非公開）
            $table->boolean('login_user_flag')->default(false);  // trueでログイン必須
            $table->boolean('admin_only_flag')->default(false);  // trueで開発中（管理者のみテスト可能）
            
            // 🌟 魔法の汎用JSONカラム
            // ツインフェイサーなら {"scroll_speed": 0.4, "gap": 140} が入る
            $table->json('custom_settings'); 

            $table->timestamps();
            $table->unique(['game_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_stages');
    }
};