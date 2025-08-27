<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'start_datetime',
        'end_datetime',
        'location',
        'online_meeting_url',
        'capacity',
        'is_online',
        'requires_registration',
        'metadata',
        'campaign_id',
        'created_by',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'metadata' => 'array',
        'is_online' => 'boolean',
        'requires_registration' => 'boolean',
    ];

    /**
     * Get the campaign that this event belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the user who created the event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the registrations for this event.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Get the attendances for this event.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    /**
     * Check if the event is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if the event is ongoing.
     */
    public function isOngoing(): bool
    {
        $now = now();
        return $this->status === 'ongoing' && 
               $this->start_datetime <= $now && 
               $this->end_datetime >= $now;
    }

    /**
     * Check if the event is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the event is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the event is a webinar.
     */
    public function isWebinar(): bool
    {
        return $this->type === 'webinar';
    }

    /**
     * Check if the event is online.
     */
    public function isOnline(): bool
    {
        return $this->is_online;
    }

    /**
     * Get the duration of the event in minutes.
     */
    public function getDurationInMinutes(): int
    {
        return $this->start_datetime->diffInMinutes($this->end_datetime);
    }

    /**
     * Get the registration count.
     */
    public function getRegistrationCount(): int
    {
        return $this->registrations()
            ->whereIn('status', ['registered', 'confirmed'])
            ->count();
    }

    /**
     * Get the attendance count.
     */
    public function getAttendanceCount(): int
    {
        return $this->attendances()->count();
    }

    /**
     * Check if the event is at capacity.
     */
    public function isAtCapacity(): bool
    {
        if ($this->capacity === null) {
            return false;
        }
        return $this->getRegistrationCount() >= $this->capacity;
    }

    /**
     * Get the remaining capacity.
     */
    public function getRemainingCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }
        return max(0, $this->capacity - $this->getRegistrationCount());
    }
}
