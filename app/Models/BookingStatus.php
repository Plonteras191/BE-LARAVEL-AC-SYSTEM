<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'status_name',
    ];

    public $timestamps = false;

    /**
     * Get all bookings with this status.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'status_id');
    }
}
