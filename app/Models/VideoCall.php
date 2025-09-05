<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class VideoCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'booking_id',
        'doctor_id',
        'patient_id',
        'status',
        'started_at',
        'ended_at',
        'duration_seconds',
        'participants',
    ];

    protected $casts = [
        'participants' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Status constants
    const STATUS_WAITING = 'waiting';
    const STATUS_ACTIVE = 'active';
    const STATUS_ENDED = 'ended';
    const STATUS_MISSED = 'missed';

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    /**
     * Generate a unique room ID
     */
    public static function generateRoomId(): string
    {
        do {
            $roomId = 'room_' . Str::random(16);
        } while (self::where('room_id', $roomId)->exists());

        return $roomId;
    }

    /**
     * Calculate call duration when ending
     */
    public function endCall(): void
    {
        $duration = $this->calculateDuration();

        $this->update([
            'status' => self::STATUS_ENDED,
            'ended_at' => now(),
            'duration_seconds' => $duration,
        ]);
    }

    /**
     * Calculate the duration of the call in seconds
     */
    private function calculateDuration(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $startTime = $this->started_at;
        $endTime = now();

        // Ensure we always get a positive duration
        if ($endTime->greaterThan($startTime)) {
            return (int) $endTime->diffInSeconds($startTime);
        }

        // If somehow ended_at is before started_at, return 0
        return 0;
    }
}
