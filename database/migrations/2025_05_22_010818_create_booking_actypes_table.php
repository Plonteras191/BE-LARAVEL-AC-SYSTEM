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
        Schema::create('booking_actypes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_service_id')->constrained()->onDelete('cascade');
            $table->foreignId('ac_type_id')->constrained()->onDelete('restrict');

            $table->unique(['booking_service_id', 'ac_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_actypes');
    }
};
