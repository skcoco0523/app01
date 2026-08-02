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
            $table->json('settings')->nullable()->after('device_id')->comment('リモコンの現在の状態（温度、モード等）');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_remotes', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
