<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToPromotionsTable extends Migration
{
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->decimal('original_price', 10, 2)->nullable()->after('price'); // Harga asli sebelum diskon
            $table->text('terms_conditions')->nullable()->after('original_price'); // Syarat & ketentuan
            $table->string('location')->nullable()->after('terms_conditions'); // Lokasi merchant
        });
    }

    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['original_price', 'terms_conditions', 'location']);
        });
    }
}