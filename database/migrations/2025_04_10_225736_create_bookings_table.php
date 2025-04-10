<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id(); // creates an auto-increment integer primary key
            $table->string('name', 255);
            $table->string('phone', 50);
            $table->string('email', 255)->nullable();
            $table->text('complete_address');
            $table->string('status', 50)->default('Pending');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
}
