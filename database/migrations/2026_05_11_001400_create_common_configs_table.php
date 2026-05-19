<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('common_configs', function (Blueprint $table) {
            $table->id();
            $table->string('config_name')->unique()->comment('設定名');
            $table->string('type')->comment('型(int, string, range)');
            $table->string('value1')->comment('設定値1');
            $table->string('value2')->comment('設定値2');
            $table->string('description')->nullable()->comment('説明');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('common_configs');
    }
};
