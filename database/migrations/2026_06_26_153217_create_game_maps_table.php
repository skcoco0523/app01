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
        Schema::create('game_maps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('game_id')->comment('親ゲームID');
            $table->string('map_key')->comment('マップ識別キー');
            $table->string('name')->comment('マップ名');
            $table->json('custom_settings')->nullable()->comment('レイヤー・物理設定等のJSON');
            $table->string('thumbnail_url')->nullable()->comment('管理用サムネイル');
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('game_list')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_maps');
    }
};
