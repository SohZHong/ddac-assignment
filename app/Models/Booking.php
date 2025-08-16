<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    const PENDING   = 0;
    const CONFIRMED = 1;
    const CANCELLED = 2;

    protected $fillable = [
        'schedule_id',
        'patient_id',
        'start_time',
        'end_time',
        'status',
    ];
    
    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    /**
     * Patient who made the booking
     */
    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * The schedule this booking belongs to
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    /**
     * Get the healthcare professional (via schedule)
     */
    public function healthcare()
    {
        return $this->schedule->healthcare();
    }

}
