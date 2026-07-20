<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('game_list')->onDelete('cascade'); //
            
            $table->string('character_key');      // 'player1', 'hero_boss' など
            $table->string('name');               // キャラ名
            $table->string('type')->index();      // 'player', 'enemy', 'npc'
            $table->integer('sort_order')->default(0); //

            // アクセス制御フラグ
            $table->boolean('enable_flag')->default(false);      
            $table->boolean('login_user_flag')->default(false); 
            $table->boolean('admin_only_flag')->default(false); 
            
            // 🌟 モーション、および各スプライトシートからパーツを引っ張る「外部参照指示書」JSON
            $table->json('motion_data');
            
            $table->timestamps();
            $table->unique(['game_id', 'character_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_characters');
    }
};