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
        Schema::table('game_items', function (Blueprint $table) {
            $table->unsignedBigInteger('sprite_sheet_id')->nullable()->after('game_id')->comment('参照元スプライトシートID');

            $table->foreign('sprite_sheet_id')->references('id')->on('game_sprite_sheets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_items', function (Blueprint $table) {
            $table->dropForeign(['sprite_sheet_id']);
            $table->dropColumn(['sprite_sheet_id']);
        });
    }
};
