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


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'status_id',
    ];

    public $timestamps = false;

    protected $dates = [
        'created_at',
    ];

    /**
     * Get the customer that owns the booking.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the status of the booking.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(BookingStatus::class, 'status_id');
    }

    /**
     * Get all technicians assigned to this booking.
     */
    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class, 'booking_technicians');
    }

    /**
     * Get all services for this booking.
     */
    public function services(): HasMany
    {
        return $this->hasMany(BookingService::class);
    }

    /**
     * Get the revenue for this booking.
     */
    public function revenue(): HasOne
    {
        return $this->hasOne(Revenue::class);
    }
}


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


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BookingService extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'service_type',
        'appointment_date',
    ];

    public $timestamps = false;

    protected $dates = [
        'appointment_date',
    ];

    /**
     * Get the booking that owns the service.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get all AC types for this service.
     */
    public function acTypes(): BelongsToMany
    {
        return $this->belongsToMany(AcType::class, 'booking_actypes', 'booking_service_id', 'ac_type_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revenue extends Model
{
    use HasFactory;

    protected $table = 'revenue';

    protected $fillable = [
        'booking_id',
        'revenue_date',
        'total_revenue',
    ];

    public $timestamps = false;

    protected $dates = [
        'revenue_date',
        'created_at',
    ];

    protected $casts = [
        'total_revenue' => 'decimal:2',
    ];

    /**
     * Get the booking that owns the revenue.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technician extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public $timestamps = false;

    /**
     * Get all bookings assigned to this technician.
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'booking_technicians');
    }
}
