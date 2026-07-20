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
        Schema::table('game_sprite_sheets', function (Blueprint $table) {
            $table->json('grid_data')->nullable()->after('pixel_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sprite_sheets', function (Blueprint $table) {
            $table->dropColumn('grid_data');
        });
    }
};
