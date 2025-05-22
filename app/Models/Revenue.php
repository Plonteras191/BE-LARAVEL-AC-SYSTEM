<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revenue extends Model
{
    use HasFactory;

    protected $table = 'revenue';

    protected $fillable = [
        'booking_id',
        'revenue_date',
        'total_revenue',
    ];

    public $timestamps = false;

    protected $dates = [
        'revenue_date',
        'created_at',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
    ];

    /**
     * Get the booking that owns the revenue.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
