<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('point_orders', function (Blueprint $table) {
            $table->string('stripe_event_id')->nullable()->unique()->after('external_payment_id')->comment('処理済みStripeイベントID');
        });
    }

    public function down(): void
    {
        Schema::table('point_orders', function (Blueprint $table) {
            $table->dropColumn('stripe_event_id');
        });
    }
};