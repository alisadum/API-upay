<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2); // Jumlah transaksi
            $table->enum('type', ['topup', 'payment']); // Jenis: topup atau pembayaran
            $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('set null'); // Link ke voucher (jika payment)
            $table->string('status')->default('pending'); // pending, completed
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
    }   
}