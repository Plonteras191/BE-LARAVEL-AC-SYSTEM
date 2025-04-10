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
}
