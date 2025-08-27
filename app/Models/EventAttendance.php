<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendance extends Model
{
    /** @use HasFactory<\Database\Factories\EventAttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'check_in_time',
        'check_out_time',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the event that this attendance belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who attended.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the attendance is present.
     */
    public function isPresent(): bool
    {
        return $this->status === 'present';
    }

    /**
     * Check if the attendance is absent.
     */
    public function isAbsent(): bool
    {
        return $this->status === 'absent';
    }

    /**
     * Check if the attendance is late.
     */
    public function isLate(): bool
    {
        return $this->status === 'late';
    }

    /**
     * Check if the attendance left early.
     */
    public function leftEarly(): bool
    {
        return $this->status === 'left_early';
    }

    /**
     * Check in the user.
     */
    public function checkIn(): void
    {
        $this->update([
            'status' => 'present',
            'check_in_time' => now(),
        ]);
    }

    /**
     * Check out the user.
     */
    public function checkOut(): void
    {
        $this->update([
            'check_out_time' => now(),
        ]);
    }

    /**
     * Mark as absent.
     */
    public function markAbsent(): void
    {
        $this->update(['status' => 'absent']);
    }

    /**
     * Mark as late.
     */
    public function markLate(): void
    {
        $this->update(['status' => 'late']);
    }

    /**
     * Mark as left early.
     */
    public function markLeftEarly(): void
    {
        $this->update(['status' => 'left_early']);
    }

    /**
     * Get the duration of attendance in minutes.
     */
    public function getAttendanceDurationInMinutes(): ?int
    {
        if (!$this->check_in_time || !$this->check_out_time) {
            return null;
        }
        return $this->check_in_time->diffInMinutes($this->check_out_time);
    }
}
