<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('life_theater_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('life_theater_id')->constrained()->onDelete('cascade'); // 作品ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // 共有先ユーザーID
            $table->boolean('admin_flag')->default(false); // 編集権限
            $table->timestamps();
            $table->unique(['life_theater_id', 'user_id']); // 二重共有を防止
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('life_theater_shares');
    }
};