<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServiceRelationshipsToRevenueHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('revenue_history', function (Blueprint $table) {
            // Add booking_id foreign key
            $table->foreignId('booking_id')
                  ->nullable()
                  ->after('total_revenue')
                  ->constrained('bookings')
                  ->onDelete('set null');

            // Add service_type column
            $table->string('service_type', 50)
                  ->nullable()
                  ->after('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('revenue_history', function (Blueprint $table) {
            // Remove foreign key constraint first
            $table->dropConstrainedForeignId('booking_id');

            // Remove service_type column
            $table->dropColumn('service_type');
        });
    }
}
