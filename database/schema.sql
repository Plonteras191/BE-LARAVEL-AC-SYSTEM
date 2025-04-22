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

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRevenueHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('revenue_history', function (Blueprint $table) {
            $table->id();
            $table->date('revenue_date');
            $table->decimal('total_revenue', 10, 2);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('revenue_history');
    }
}

--NEW ADDED
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


--UPDATED ACTYPE AC types are directly linked to specific services rather than just bookings.

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

--NEW ADDED REMOVE SERVICE_TYPE  IN REVENUE HISTORY
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


--NEW ADDED UNIQUE ON REVENUE HISTORY
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
