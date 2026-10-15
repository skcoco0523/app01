<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique()->comment('自社発番の注文番号');
            
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->comment('ユーザーID');
            $table->string('provider', 50)->comment('決済プロバイダ名（stripeなど）');
            
            $table->string('external_order_id')->nullable()->unique()->comment('決済代行側のセッションID等');
            $table->string('external_payment_id')->nullable()->comment('決済代行側のトランザクションID');
            
            $table->string('config_name')->comment('購入パック設定キー (例: po_pack1)');
            $table->unsignedInteger('amount')->comment('決済金額（円）');
            $table->unsignedInteger('points')->comment('付与有償ポイント数');
            
            $table->unsignedInteger('valid_days')->default(180)->comment('ポイント有効期限（日数）');
            $table->string('status', 20)->default('pending')->comment('pending: 決済待ち, paid: 完了, failed: 失敗, canceled: 中断');
            
            $table->json('metadata')->nullable()->comment('決済時の追加データ・Webhookレスポンス');

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('external_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_orders');
    }
};