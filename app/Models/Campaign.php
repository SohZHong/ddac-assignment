<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'status',
        'start_date',
        'end_date',
        'target_audience',
        'target_reach',
        'budget',
        'location',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'array',
        'budget' => 'decimal:2',
    ];

    /**
     * Get the user who created the campaign.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the events associated with this campaign.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Check if the campaign is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the campaign is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if the campaign is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the campaign is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if the campaign is currently running (active and within date range).
     */
    public function isRunning(): bool
    {
        $now = now();
        return $this->isActive() && 
               $this->start_date <= $now && 
               $this->end_date >= $now;
    }

    /**
     * Get the duration of the campaign in days.
     */
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get the progress percentage of the campaign.
     */
    public function getProgressPercentage(): float
    {
        if ($this->isDraft() || $this->isCancelled()) {
            return 0;
        }

        if ($this->isCompleted()) {
            return 100;
        }

        $totalDays = $this->getDurationInDays();
        if ($totalDays === 0) {
            return 100;
        }

        $elapsedDays = $this->start_date->diffInDays(now());
        $progress = ($elapsedDays / $totalDays) * 100;

        return min(100, max(0, $progress));
    }
}
