<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LivekitRoom extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_name',
        'event_id',
        'created_by',
        'status',
        'max_participants',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(LivekitParticipant::class, 'room_id');
    }

    public function isLive(): bool
    {
        return $this->status === 'live';
    }

    public function isEnded(): bool
    {
        return $this->status === 'ended';
    }

    public function start(): void
    {
        $this->update([
            'status' => 'live',
            'started_at' => now(),
        ]);
    }

    public function end(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => now(),
        ]);
    }
}
