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
        Schema::table('iot_devices', function (Blueprint $table) {
            $table->text('ww_data')->nullable()->after('admin_user_id')->comment('音声指紋データ (JSON形式: Z-Score正規化済み整数-128〜127のカンマ区切り文字列配列)');
            // ww_score: ユーザーが任意で設定できる判定しきい値を追加
            $table->integer('ww_score')->default(80)->after('ww_data')->comment('音声照合のしきい値スコア (0-100)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iot_devices', function (Blueprint $table) {
            $table->dropColumn(['ww_data', 'ww_score']);
        });
    }
};
