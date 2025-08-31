<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('promotions', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('category');
                $table->dropColumn('category'); // Hapus kolom lama jika ada
                $table->foreign('category_id')
                      ->references('id')
                      ->on('categories')
                      ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            if (!Schema::hasColumn('promotions', 'category')) {
                $table->string('category')->after('location');
            }
        });
    }
};
