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
        Schema::table('virtual_remotes', function (Blueprint $table) {
            // device_id を追加。デフォルト値は 0
            $table->unsignedBigInteger('device_id')->default(0)->after('blade_id')->comment('送信先IoTデバイスID (ライブラリ型用)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_remotes', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
};
