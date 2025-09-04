<?php

namespace App\Events;

use App\Models\VideoCall;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoCallEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $videoCall;

    /**
     * Create a new event instance.
     */
    public function __construct(VideoCall $videoCall)
    {
        $this->videoCall = $videoCall;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('video-call.' . $this->videoCall->room_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'call.ended';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->videoCall->room_id,
            'ended_at' => $this->videoCall->ended_at->toISOString(),
            'duration' => $this->videoCall->call_duration,
            'ended_by' => $this->videoCall->ended_by,
        ];
    }
}
