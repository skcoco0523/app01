<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_theater_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_theater_id')->constrained()->onDelete('cascade'); // 親作品ID
            $table->integer('step_order')->default(0); // 表示順序
            $table->string('label')->nullable(); // タイムライン用ラベル（例: 0か月）
            $table->integer('line')->default(0); // タイムライン位置用数値
            $table->string('title')->nullable(); // スライドタイトル
            $table->string('subtitle')->nullable(); // スライドサブタイトル
            $table->text('content')->nullable(); // 説明文テキスト
            $table->string('slide_date')->nullable(); // 日付表示（例: 2026.04.02）

            // カラム名を life_theater_media_id に統一
            $table->foreignId('life_theater_media_id')->nullable()->constrained('life_theater_media')->onDelete('set null');

            $table->json('config_data')->nullable(); // スライド設定データ（JSON）
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_theater_slides');
    }
};