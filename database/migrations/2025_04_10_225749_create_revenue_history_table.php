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
