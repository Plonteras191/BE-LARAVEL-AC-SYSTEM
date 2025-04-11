<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueHistory extends Model
{
    protected $table = 'revenue_history';
    protected $fillable = ['revenue_date', 'total_revenue'];
    public $timestamps = false;
}
