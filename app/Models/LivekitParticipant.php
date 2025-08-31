<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LivekitParticipant extends Model
{
    protected $fillable = [
        'room_id',
        'user_id',
        'participant_identity',
        'joined_at',
        'left_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(LivekitRoom::class, 'room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leave(): void
    {
        $this->update(['left_at' => now()]);
    }

    public function isActive(): bool
    {
        return is_null($this->left_at);
    }
}
