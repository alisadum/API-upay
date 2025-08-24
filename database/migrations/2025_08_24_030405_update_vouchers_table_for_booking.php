<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateVouchersTableForBooking extends Migration
{
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->date('booking_date')->nullable()->after('qr_path'); // Tanggal booking
            $table->time('booking_time')->nullable()->after('booking_date'); // Jam booking
            $table->enum('status', ['pending', 'booked', 'used'])->default('pending')->after('is_redeemed'); // Status voucher
        });
    }

    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['booking_date', 'booking_time', 'status']);
        });
    }
}