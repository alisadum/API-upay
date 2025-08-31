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
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'booking_date')) {
                $table->dropColumn('booking_date');
            }
            if (Schema::hasColumn('vouchers', 'booking_time')) {
                $table->dropColumn('booking_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->date('booking_date')->nullable();
            $table->time('booking_time')->nullable();
        });
    }
};
