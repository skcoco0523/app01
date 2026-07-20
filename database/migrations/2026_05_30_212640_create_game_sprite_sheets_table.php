<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_sprite_sheets', function (Blueprint $table) {
            $table->id();
            // 画像ファイル名（例: 'hero_body.png', 'common_weapons.png', 'bg_lava_dungeon.png'）
            $table->string('filename')->unique();
            // 素材のカテゴリ分類
            $table->string('category')->index()->comment('\app01\storage\app\public\sprite_sheet 配下のフォルダ名と一致');;
            // この画像から切り出されたすべてのパーツ（フレーム）の座標データを集約
            $table->json('pixel_data'); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_sprite_sheets');
    }
};