<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateVouchersTable extends Migration
{
    public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('promotion_id'); // midtrans, whatsapp
            $table->string('qr_path')->nullable()->after('code'); // Path QR code
            $table->string('proof_path')->nullable()->after('qr_path'); // Bukti bayar WhatsApp
            $table->boolean('is_paid')->default(false)->after('is_redeemed'); // Status pembayaran
            $table->string('transaction_id')->nullable()->after('is_paid'); // ID transaksi Midtrans
        });
    }

    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'qr_path', 'proof_path', 'is_paid', 'transaction_id']);
        });
    }
}