<?php
// app/Models/Booking.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'complete_address', 'status'
    ];

    public $timestamps = false;
}
