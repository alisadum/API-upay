<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePointLogsTable extends Migration
{
    public function up()
    {
        Schema::create('point_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points')->default(0); // Jumlah poin yang didapat
            $table->string('source')->nullable(); // Sumber poin (misal: purchase, booking)
            $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('set null'); // Link ke voucher
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('point_logs');
    }
}