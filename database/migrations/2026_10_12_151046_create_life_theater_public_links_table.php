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
        Schema::create('life_theater_public_links', function (Blueprint $table) {
            $table->id();
            
            // 対象の作品ID
            $table->foreignId('life_theater_id')->constrained('life_theaters')->onDelete('cascade');
            // 発行したユーザーID
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('発行者ユーザーID');

            // URLのパスになる一意のトークン
            $table->string('token', 64)->unique()->comment('公開用の一意なトークン');
            // ユーザー識別用のラベル（例: SNS用、LINE用）
            $table->string('memo', 50)->nullable()->comment('リンク識別用メモ');
            // 広告の有無（true: 広告なし/プレミアム, false: 広告あり/無料）
            $table->boolean('is_ad_free')->default(false)->comment('true: 広告なし, false: 広告あり');
            // 閲覧回数カウンター
            $table->unsignedBigInteger('view_count')->default(0)->comment('アクセス閲覧回数');
            // パスワード保護（必要な場合のみ使用）
            $table->string('password')->nullable()->comment('合言葉/閲覧パスワード');
            // リンクの有効/無効（公開停止フラグ）
            $table->boolean('is_active')->default(true)->comment('true: 公開中, false: 停止中');
            // 有効期限（nullなら無期限）
            $table->timestamp('expires_at')->nullable()->comment('有効期限');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('life_theater_public_links');
    }
};