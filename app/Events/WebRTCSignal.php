<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRTCSignal implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $roomId;
    public $fromUserId;
    public $toUserId;
    public $signal;

    /**
     * Create a new event instance.
     */
    public function __construct(string $roomId, int $fromUserId, ?int $toUserId, array $signal)
    {
        $this->roomId = $roomId;
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
        $this->signal = $signal;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('video-call.' . $this->roomId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'webrtc.signal';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'from_user_id' => $this->fromUserId,
            'to_user_id' => $this->toUserId,
            'signal' => $this->signal,
            'timestamp' => now()->toISOString(),
        ];
    }
}
