<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_point', function (Blueprint $table) {
            // point_ordersテーブルが作られた後に追加するため、外部キー制約をつける
            $table->foreignId('point_order_id')->nullable()->after('user_id')
                  ->constrained('point_orders')->onDelete('set null')
                  ->comment('紐付く決済注文ID（有償購入時のみ）');
        });
    }

    public function down(): void
    {
        Schema::table('user_point', function (Blueprint $table) {
            $table->dropForeign(['point_order_id']);
            $table->dropColumn('point_order_id');
        });
    }
};