<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPointsAndMembershipToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('email'); // Total poin user
            $table->enum('membership_level', ['silver', 'gold'])->default('silver')->after('points'); // Level membership
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['points', 'membership_level']);
        });
    }
}