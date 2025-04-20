<?php
// app/Models/BookingActype.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingActype extends Model
{
    protected $fillable = [
        'booking_service_id', 'ac_type'  // Changed from booking_id to booking_service_id
    ];

    public $timestamps = false;

    // Add relationship to BookingService
    public function bookingService()
    {
        return $this->belongsTo(BookingService::class);
    }
}
