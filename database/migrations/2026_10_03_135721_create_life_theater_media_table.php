<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_theater_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_theater_id')->constrained()->onDelete('cascade'); // 親作品ID
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // アップロードユーザー
            $table->string('name')->nullable(); // 画像のラベル・名称（例: 「山田太郎」「背景」）
            $table->string('image_s3_key'); // S3の画像パス
            $table->string('category')->nullable(); // カテゴリ（character, background, general等）
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_theater_media');
    }
};