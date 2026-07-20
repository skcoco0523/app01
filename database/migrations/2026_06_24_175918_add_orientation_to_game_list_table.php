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
        Schema::table('game_list', function (Blueprint $blueprint) {
            $blueprint->string('orientation')->default('landscape')->after('view_mode')->comment('画面の向き: landscape, portrait');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_list', function (Blueprint $blueprint) {
            $blueprint->dropColumn('orientation');
        });
    }
};
