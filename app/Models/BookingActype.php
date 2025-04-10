<?php
// app/Models/BookingActype.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingActype extends Model
{
    protected $fillable = [
        'booking_id', 'ac_type'
    ];

    public $timestamps = false;
}
