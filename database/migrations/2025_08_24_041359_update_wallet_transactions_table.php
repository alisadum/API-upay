<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWalletTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('type', ['topup', 'purchase'])->change(); // Update tipe
        });
    }

    public function down()
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->enum('type', ['topup'])->change();
        });
    }
}