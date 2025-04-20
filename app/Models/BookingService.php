<?php
// app/Models/BookingService.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingService extends Model
{
    protected $fillable = [
        'booking_id', 'service_type', 'appointment_date'
    ];

    public $timestamps = false;

    // Add relationship to BookingActype
    public function acTypes()
    {
        return $this->hasMany(BookingActype::class);
    }

    // Maintain relationship to Booking
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
