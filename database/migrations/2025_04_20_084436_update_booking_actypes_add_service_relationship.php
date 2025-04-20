<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBookingActypesAddServiceRelationship extends Migration
{
    public function up()
    {
        // First drop the existing booking_actypes table since we're restructuring it
        Schema::dropIfExists('booking_actypes');

        // Create the new booking_actypes table with service relationship
        Schema::create('booking_actypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_service_id')
                  ->constrained('booking_services')
                  ->onDelete('cascade');
            $table->string('ac_type', 50);
        });
    }

    public function down()
    {
        // Drop the new table
        Schema::dropIfExists('booking_actypes');

        // Recreate the original table structure if needed to roll back
        Schema::create('booking_actypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->onDelete('cascade');
            $table->string('ac_type', 50);
        });
    }
}
