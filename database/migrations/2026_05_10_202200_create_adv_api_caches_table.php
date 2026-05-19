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
        Schema::create('adv_api_caches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable()->comment('カテゴリID');
            $table->string('keyword')->comment('検索語');
            $table->longText('response_json')->comment('API結果');
            $table->timestamp('expired_at')->comment('有効期限');
            $table->timestamps();

            $table->index(['category_id', 'keyword']);
            $table->foreign('category_id')->references('id')->on('adv_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adv_api_caches');
    }
};
