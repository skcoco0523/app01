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
        Schema::table('virtual_remote_blades', function (Blueprint $table) {
            // blade_name の後ろに library_flag (0:学習型, 1:ライブラリ型) を追加
            $table->boolean('library_flag')->default(false)->after('blade_name')->comment('0:学習型(RAW), 1:ライブラリ型');
            // (プロトコル識別子) を追加
            $table->string('protocol', 50)->nullable()->after('library_flag')->comment('プロトコル名 (例: PANASONIC_AC)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_remote_blades', function (Blueprint $table) {
            $table->dropColumn(['library_flag', 'protocol']);
        });
    }
};
