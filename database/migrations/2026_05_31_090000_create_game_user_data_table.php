<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_user_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('game_id')->constrained('game_list')->onDelete('cascade');

            // 基本的な数値データ
            $table->bigInteger('experience')->default(0); // 経験値
            $table->bigInteger('currency')->default(0);   // 所持金
            $table->integer('current_stage')->default(1); // 到達ステージ

            // ゲーム固有のフラグや状態（クリア済みフラグなど）をJSONで柔軟に保持
            $table->json('unlocked_features')->nullable(); 
            $table->json('custom_stats')->nullable();
            
            // 追加：スタミナ・ログイン管理用のメタデータ（汎用システムに必須）
            $table->timestamp('last_stamina_updated_at')->nullable(); // スタミナ自然回復の基準時間
            $table->timestamp('last_played_at')->nullable();

            $table->timestamps();

            // 1ユーザー1ゲームにつき1レコードに限定
            $table->unique(['user_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_user_data');
    }
};