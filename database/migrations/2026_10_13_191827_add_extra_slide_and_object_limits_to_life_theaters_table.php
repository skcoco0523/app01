<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('life_theaters', function (Blueprint $table) {
            $table->integer('extra_slide_limit')->default(0)->after('extra_media_limit')->comment('追加スライド枠');
            $table->integer('extra_object_limit')->default(0)->after('extra_slide_limit')->comment('追加オブジェクト枠');
        });
    }

    public function down(): void
    {
        Schema::table('life_theaters', function (Blueprint $table) {
            $table->dropColumn(['extra_slide_limit', 'extra_object_limit']);
        });
    }
};