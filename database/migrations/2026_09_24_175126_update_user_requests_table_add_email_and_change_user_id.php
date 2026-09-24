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
        Schema::table('user_requests', function (Blueprint $table) {
            // user_id を NULL 許可に変更
            $table->unsignedBigInteger('user_id')->nullable()->change();
            
            // email カラム（NULL許可）を user_id の後ろに追加
            $table->string('email')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_requests', function (Blueprint $table) {
            // email カラムを削除
            $table->dropColumn('email');
            
            // user_id を NOT NULL に戻す
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }
};