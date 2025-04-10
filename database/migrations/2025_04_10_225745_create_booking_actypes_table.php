<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingActypesTable extends Migration
{
    public function up()
    {
        Schema::create('booking_actypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->onDelete('cascade');
            $table->string('ac_type', 50);
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_actypes');
    }
}
