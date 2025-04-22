<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveServiceTypeFromRevenueHistory extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('revenue_history', function (Blueprint $table) {
            // Drop the service_type column if it exists
            if (Schema::hasColumn('revenue_history', 'service_type')) {
                $table->dropColumn('service_type');
            }
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('revenue_history', function (Blueprint $table) {
            // Re-add the service_type column if migration is rolled back
            if (!Schema::hasColumn('revenue_history', 'service_type')) {
                $table->string('service_type')->nullable();
            }
        });
    }
}
