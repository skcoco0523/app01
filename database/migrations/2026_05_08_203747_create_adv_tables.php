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
        // 広告カテゴリ
        Schema::create('adv_categories', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('search_keywords')->nullable();
            $blueprint->boolean('enable_flag')->default(true);
            $blueprint->timestamps();
        });

        // アンケート履歴
        Schema::create('adv_researches', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('user_id');
            $blueprint->unsignedBigInteger('adv_category_id');
            $blueprint->integer('display_seconds')->default(0);
            $blueprint->integer('score')->default(0);
            $blueprint->string('type'); // select / detail_view / dislike
            $blueprint->timestamp('created_at')->useCurrent();

            $blueprint->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $blueprint->foreign('adv_category_id')->references('id')->on('adv_categories')->onDelete('cascade');
        });

        // ユーザースコア蓄積
        Schema::create('adv_user_scores', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->unsignedBigInteger('user_id');
            $blueprint->unsignedBigInteger('adv_category_id');
            $blueprint->integer('score')->default(0);
            $blueprint->timestamps();

            $blueprint->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $blueprint->foreign('adv_category_id')->references('id')->on('adv_categories')->onDelete('cascade');
            $blueprint->unique(['user_id', 'adv_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adv_user_scores');
        Schema::dropIfExists('adv_researches');
        Schema::dropIfExists('adv_categories');
    }
};
