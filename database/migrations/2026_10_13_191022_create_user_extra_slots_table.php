<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_extra_slots', function (Blueprint $table) {
            $table->id();
            // user_idは外部キー制約を付け、ユーザー削除時に連動して消えるようにする
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // ライフシアターの作品作成枠（追加分）
            $table->integer('life_theater_limit')->default(0)->comment('ライフシアター作品の追加作成枠');
            
            // ※今後スマートリモコンなどの枠が増えたら、ここにカラムを足すマイグレーションを作成します
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_extra_slots');
    }
};