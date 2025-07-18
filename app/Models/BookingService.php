<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BookingService extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'service_type',
        'appointment_date',
    ];

    public $timestamps = false;

    protected $dates = [
        'appointment_date',
    ];

    /**
     * Get the booking that owns the service.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get all AC types for this service.
     */
    public function acTypes(): BelongsToMany
    {
        return $this->belongsToMany(AcType::class, 'booking_actypes', 'booking_service_id', 'ac_type_id');
    }
}
