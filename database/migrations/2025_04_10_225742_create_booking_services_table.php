<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingServicesTable extends Migration
{
    public function up()
    {
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id();
            // Create a foreign key column that references bookings table
            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->onDelete('cascade');
            $table->string('service_type', 50);
            $table->date('appointment_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('booking_services');
    }
}
