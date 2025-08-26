<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    // Booking status
    const PENDING   = 0;
    const CONFIRMED = 1;
    const CANCELLED = 2;
    
    // Special status for standalone assessments
    const ASSESSMENT_ONLY = 3;

    // Risk level
    const LOW   = 0;
    const MID   = 1;
    const HIGH  = 2;

    protected $fillable = [
        'schedule_id',
        'patient_id',
        'start_time',
        'end_time',
        'status',
        'healthcare_comments',
        'risk_level',
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
        return $this->hasOneThrough(
            User::class,
            Schedule::class,
            'id',           // Foreign key on schedules table
            'id',           // Foreign key on users table
            'schedule_id',  // Local key on bookings table
            'healthcare_id' // Local key on schedules table
        );
    }

    /**
     * Get the quiz response associated with this booking
     */
    public function quizResponse()
    {
        return $this->hasOne(QuizResponse::class);
    }
}
