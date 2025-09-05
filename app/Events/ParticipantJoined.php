<?php

namespace App\Events;

use App\Models\VideoCall;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantJoined implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $videoCall;
    public $user;

    public function __construct(VideoCall $videoCall, User $user)
    {
        $this->videoCall = $videoCall;
        $this->user = $user;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('video-call.' . $this->videoCall->room_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'participant.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'role' => $this->user->id === $this->videoCall->doctor_id ? 'doctor' : 'patient',
            'joined_at' => now()->toISOString(),
        ];
    }
}
