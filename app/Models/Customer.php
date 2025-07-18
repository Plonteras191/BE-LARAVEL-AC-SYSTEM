<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'complete_address',
    ];

    public $timestamps = false;

    /**
     * Get all bookings for this customer.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
