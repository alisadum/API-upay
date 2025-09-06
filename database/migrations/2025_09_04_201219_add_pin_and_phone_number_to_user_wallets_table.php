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
        Schema::table('user_wallets', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->unique()->after('balance');
            $table->string('pin')->nullable()->after('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_wallets', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'pin']);
        });
    }
};