<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_name',
    ];

    public $timestamps = false;

    /**
     * Get all booking services that use this AC type.
     */
    public function bookingServices(): BelongsToMany
    {
        return $this->belongsToMany(BookingService::class, 'booking_actypes', 'ac_type_id', 'booking_service_id');
    }
}
