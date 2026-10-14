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
        Schema::table('users', function (Blueprint $table) {
            // ★ 旧ポイントカラムを削除
            $table->dropColumn(['free_point', 'pay_point']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ロールバック用に再追加する処理
            $table->unsignedInteger('free_point')->default(0)->comment('無償ポイント');
            $table->unsignedInteger('pay_point')->default(0)->comment('有償ポイント');
        });
    }
};