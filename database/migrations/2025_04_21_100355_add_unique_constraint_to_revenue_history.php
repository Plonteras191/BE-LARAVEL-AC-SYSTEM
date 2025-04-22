<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToRevenueHistory extends Migration
{
    public function up()
    {
        Schema::table('revenue_history', function (Blueprint $table) {
            $table->unique('booking_id');
        });
    }

    public function down()
    {
        Schema::table('revenue_history', function (Blueprint $table) {
            $table->dropUnique(['booking_id']);
        });
    }
}
