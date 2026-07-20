<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_list', function (Blueprint $table) {
            $table->id();
            $table->string('game_key');                // 'twin_facer' などの識別キー
            $table->string('title');                   // ゲームタイトル
            // ゲーム全体のカメラ視点を定義
            $table->string('view_mode')->default('side_view')->comment('side_view:横スクロール(アクション等) top_down:見下ろし(RPG等) fixed_screen:1画面固定(パズル等)');
            $table->string('description')->nullable(); // ゲーム説明文
            $table->integer('version')->default(1);    // クライアント側のキャッシュ制御用バージョン
            
            // 🌟 アクセス制御フラグ
            $table->boolean('enable_flag')->default(false);       // falseでメンテナンス（非公開）
            $table->boolean('login_user_flag')->default(false);  // trueでログイン必須
            $table->boolean('admin_only_flag')->default(false);  // trueで開発中（管理者のみテスト可能）
            
            $table->timestamps();

            $table->unique(['game_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_list');
    }
};