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
            // 無料ポイント（初期値100pt、毎月リセット対象）
            $table->integer('free_point')->default(100)->after('remember_token');
            
            // 有償ポイント（初期値0pt、繰り越し可能）
            $table->integer('pay_point')->default(0)->after('free_point');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['free_point', 'pay_point']);
        });
    }
};
