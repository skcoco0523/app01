<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_theaters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 作成者
            $table->string('title'); // タイトル
            $table->string('subtitle')->nullable(); // サブタイトル
            $table->string('bgm_type')->nullable(); // BGM識別子
            $table->integer('theme_color_num')->default(0); // テーマカラー番号
            $table->boolean('edit_lock_flag')->default(false); // 編集ロック
            $table->string('plan_type')->default('free'); // プラン種別
            $table->integer('used_points')->default(0); // この作品に実際に消費した累計ポイント数
            $table->json('config_data')->nullable(); // 設定データ（JSON）
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_theaters');
    }
};