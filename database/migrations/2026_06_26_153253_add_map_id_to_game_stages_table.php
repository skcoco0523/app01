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
        Schema::table('game_stages', function (Blueprint $table) {
            $table->unsignedBigInteger('map_id')->nullable()->after('game_id')->comment('使用するマップのID');
            
            $table->foreign('map_id')->references('id')->on('game_maps')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_stages', function (Blueprint $table) {
            $table->dropForeign(['map_id']);
            $table->dropColumn('map_id');
        });
    }
};
