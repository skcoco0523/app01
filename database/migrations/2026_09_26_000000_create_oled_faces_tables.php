<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oled_faces', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('event_type')->unique(); 
            $table->integer('interval_ms')->default(150);
            $table->longText('editor_json')->nullable();
            $table->longText('compiled_device_json')->nullable(); 
            $table->integer('point_cost')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oled_faces');
    }
};
