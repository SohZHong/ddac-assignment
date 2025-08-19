<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QuizResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'booking_id',
        'answers',
        'completed_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Response belongs to a quiz
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Response belongs to a booking
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // Get the patient who answered
    public function patient()
    {
        return $this->booking->patient();
    }
}
