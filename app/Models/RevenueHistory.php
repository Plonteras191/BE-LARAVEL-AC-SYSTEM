<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueHistory extends Model
{
    protected $table = 'revenue_history';
    protected $fillable = ['revenue_date', 'total_revenue', 'booking_id', 'service_type'];
    public $timestamps = false;

    /**
     * Get the booking that generated this revenue
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
