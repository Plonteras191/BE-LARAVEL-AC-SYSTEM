<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'status_id',
    ];

    public $timestamps = false;

    protected $dates = [
        'created_at',
    ];

    /**
     * Get the customer that owns the booking.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the status of the booking.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(BookingStatus::class, 'status_id');
    }

    /**
     * Get all technicians assigned to this booking.
     */
    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class, 'booking_technicians');
    }

    /**
     * Get all services for this booking.
     */
    public function services(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }

    /**
     * Get the revenue for this booking.
     */
    public function revenue(): HasOne
    {
        return $this->hasOne(Revenue::class);
    }
}
