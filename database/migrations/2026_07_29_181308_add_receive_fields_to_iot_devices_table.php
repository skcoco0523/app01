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
            $table->string('receive_command', 100)->nullable()->after('status')->comment('受信した最新のコマンド名');
            $table->text('receive_data')->nullable()->after('receive_command')->comment('受信した最新のデータ');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('iot_devices', function (Blueprint $table) {
            $table->dropColumn(['receive_command', 'receive_data']);
        });
    }
};
