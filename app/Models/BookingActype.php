<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingActype extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_service_id',
        'ac_type_id',
    ];

    public $timestamps = false;

    /**
     * Get the booking service that owns the booking AC type.
     */
    public function bookingService(): BelongsTo
    {
        return $this->belongsTo(BookingService::class);
    }

    /**
     * Get the AC type that owns the booking AC type.
     */
    public function acType(): BelongsTo
    {
        return $this->belongsTo(AcType::class);
    }
}
