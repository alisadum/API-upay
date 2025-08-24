<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePromotionsTable extends Migration
{
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('category')->nullable()->after('title'); // e.g., salon, spa, gym
            $table->dateTime('start_time')->nullable()->after('category');
            $table->dateTime('end_time')->nullable()->after('start_time');
            $table->integer('available_seats')->nullable()->after('end_time');
            $table->string('photo_path')->nullable()->after('available_seats');
            $table->text('reject_reason')->nullable()->after('is_approved');
        });
    }

    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['category', 'start_time', 'end_time', 'available_seats', 'photo_path', 'reject_reason']);
        });
    }
}