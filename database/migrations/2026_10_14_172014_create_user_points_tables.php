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
        // 1. ポイントロット台帳テーブル
        Schema::create('user_point', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('ユーザーID');
            $table->string('type', 10)->comment('paid:有償, free:無償');
            $table->unsignedInteger('point')->comment('付与時のポイント数');
            $table->unsignedInteger('point_rem')->comment('未消費の残ポイント数');
            $table->dateTime('expired_at')->nullable()->comment('有効期限');
            $table->timestamps();

            // ★ type を含めた複合インデックスに変更して残高取得・消費を最高速化
            $table->index(['user_id', 'type', 'point_rem', 'expired_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_point');
    }
};