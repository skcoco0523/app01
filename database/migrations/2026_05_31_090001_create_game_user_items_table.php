<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_user_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('game_id')->constrained('game_list')->onDelete('cascade');
            $table->foreignId('game_item_id')->constrained('game_items')->onDelete('cascade');

            $table->integer('quantity')->default(1); // 所持数
            
            $table->timestamps();

            // 重複所持を防ぐ（個数加算で対応するため）
            $table->unique(['user_id', 'game_id', 'game_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_user_items');
    }
};