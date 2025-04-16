<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterRevenueHistoryAddBookingAndServiceType extends Migration
{
    public function up()
    {
        Schema::table('revenue_history', function (Blueprint $table) {
            // Add booking_id foreign key if it doesn't exist
            if (!Schema::hasColumn('revenue_history', 'booking_id')) {
                $table->foreignId('booking_id')
                      ->constrained('bookings')
                      ->onDelete('cascade');
            }

            // Add service_type column if it doesn't exist
            if (!Schema::hasColumn('revenue_history', 'service_type')) {
                $table->string('service_type')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('revenue_history', function (Blueprint $table) {
            // Drop foreign key constraint first
            if (Schema::hasColumn('revenue_history', 'booking_id')) {
                $table->dropForeign(['booking_id']);
                $table->dropColumn('booking_id');
            }

            if (Schema::hasColumn('revenue_history', 'service_type')) {
                $table->dropColumn('service_type');
            }
        });
    }
}
