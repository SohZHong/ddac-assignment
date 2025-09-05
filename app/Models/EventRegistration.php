<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    /** @use HasFactory<\Database\Factories\EventRegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'status',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the event that this registration belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who registered.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the registration is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if the registration is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the registration is waitlisted.
     */
    public function isWaitlisted(): bool
    {
        return $this->status === 'waitlisted';
    }

    /**
     * Confirm the registration.
     */
    public function confirm(): void
    {
        $this->update(['status' => 'confirmed']);
    }

    /**
     * Cancel the registration.
     */
    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    /**
     * Waitlist the registration.
     */
    public function waitlist(): void
    {
        $this->update(['status' => 'waitlisted']);
    }
}
