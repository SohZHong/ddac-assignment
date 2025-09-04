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

class VideoCallCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $videoCall;

    public function __construct(VideoCall $videoCall)
    {
        $this->videoCall = $videoCall;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->videoCall->patient_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'video-call.created';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->videoCall->room_id,
            'doctor_name' => $this->videoCall->doctor->name,
            'booking_id' => $this->videoCall->booking_id,
            'message' => 'Doctor is ready for video consultation',
        ];
    }
}
