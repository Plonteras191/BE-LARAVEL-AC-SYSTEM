<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTechnician extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'technician_id',
    ];

    public $timestamps = false;

    /**
     * Get the booking that owns the booking technician.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the technician that owns the booking technician.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class);
    }
}
