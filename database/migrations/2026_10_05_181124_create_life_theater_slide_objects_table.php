<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('life_theater_slide_objects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_theater_slide_id')->constrained('life_theater_slides')->onDelete('cascade');
            
            // 使用する画像（ライブラリ画像が削除されたらNULL化）
            $table->foreignId('life_theater_media_id')->nullable()->constrained('life_theater_media')->onDelete('set null');

            $table->string('type')->default('character'); // 種別（character, bubble, caption, stamp 等）
            $table->string('name')->nullable();           // 名前・ラベル（例: 「お父さん」「メモ1」）
            $table->text('text')->nullable();            // 吹き出し内の台詞やテキスト

            $table->integer('sort_order')->default(0);    // 重なり順・表示順

            // 見た目の装飾データのみJSON化（座標、サイズ、カラー、フォント等）
            // 例: {"x": 100, "y": 200, "width": 80, "color": "#ffffff", "font_size": 14}
            $table->json('object_config')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('life_theater_slide_objects');
    }
};
