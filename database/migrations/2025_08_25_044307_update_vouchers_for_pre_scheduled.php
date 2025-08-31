<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (Schema::hasColumn('vouchers', 'booking_date')) {
                $table->dropColumn('booking_date');
            }
            if (Schema::hasColumn('vouchers', 'booking_time')) {
                $table->dropColumn('booking_time');
            }

            if (!Schema::hasColumn('vouchers', 'start_at')) {
                $table->dateTime('start_at')->nullable()->after('proof_path');
            }
            if (!Schema::hasColumn('vouchers', 'expire_at')) {
                $table->dateTime('expire_at')->nullable()->after('start_at');
            }

            $table->enum('status', ['pending', 'active', 'redeemed'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            if (!Schema::hasColumn('vouchers', 'booking_date')) {
                $table->date('booking_date')->nullable()->after('qr_path');
            }
            if (!Schema::hasColumn('vouchers', 'booking_time')) {
                $table->time('booking_time')->nullable()->after('booking_date');
            }

            if (Schema::hasColumn('vouchers', 'start_at')) {
                $table->dropColumn('start_at');
            }
            if (Schema::hasColumn('vouchers', 'expire_at')) {
                $table->dropColumn('expire_at');
            }

            $table->enum('status', ['pending', 'booked', 'used'])->default('pending')->change();
        });
    }
};
