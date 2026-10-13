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
        Schema::table('life_theaters', function (Blueprint $table) {
            // ポイント等で追加購入した画像ライブラリ枠の数 (初期値: 0)
            // plan_type カラムの後に配置します
            $table->integer('extra_media_limit')
                  ->default(0)
                  ->after('plan_type')
                  ->comment('追加購入した画像ライブラリ枠数');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('life_theaters', function (Blueprint $table) {
            // ロールバック時にカラムを削除
            $table->dropColumn('extra_media_limit');
        });
    }
};