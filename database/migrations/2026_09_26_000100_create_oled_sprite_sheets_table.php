<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oled_sprite_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path'); // storage/app/public/... または public/...
            // この画像から切り出されたすべてのパーツ（フレーム）の座標データを集約
            $table->json('part_data'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oled_sprite_sheets');
    }
};
