<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('promo_type')->default('discount_percent'); // default ke tipe diskon
            $table->integer('buy_quantity')->nullable(); // jumlah beli untuk promo buy_get_free
            $table->integer('free_quantity')->nullable(); // jumlah gratis untuk promo buy_get_free
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['promo_type', 'buy_quantity', 'free_quantity']);
        });
    }
};