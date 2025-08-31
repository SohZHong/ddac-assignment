<?php

namespace App\Services;

use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\RoomCreateOptions;
use App\Models\LivekitRoom;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LivekitRoomService
{
    private RoomServiceClient $roomServiceClient;

    public function __construct()
    {
        $this->roomServiceClient = new RoomServiceClient(
            config('services.livekit.http_url'),
            config('services.livekit.api_key'),
            config('services.livekit.api_secret')
        );
    }

    public function createRoom(Event $event, User $creator, int $maxParticipants = 100): LivekitRoom
    {
        $roomName = 'event-' . $event->id . '-' . Str::random(8);

        // Create room in LiveKit server
        $roomOptions = (new RoomCreateOptions())
            ->setName($roomName)
            ->setEmptyTimeout(300) // 5 minutes
            ->setMaxParticipants($maxParticipants);

        $this->roomServiceClient->createRoom($roomOptions);

        // Create room record in database
        return LivekitRoom::create([
            'room_name' => $roomName,
            'event_id' => $event->id,
            'created_by' => $creator->id,
            'status' => 'scheduled',
            'max_participants' => $maxParticipants,
        ]);
    }

    public function startRoom(LivekitRoom $room): void
    {
        $room->start();
    }

    public function endRoom(LivekitRoom $room): void
    {
        $room->end();
        
        // Delete room from LiveKit server
        try {
            $this->roomServiceClient->deleteRoom($room->room_name);
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            Log::error('Failed to delete LiveKit room: ' . $e->getMessage());
        }
    }

    public function getRoomParticipants(LivekitRoom $room): array
    {
        try {
            $livekitRoom = $this->roomServiceClient->listRooms();
            $roomData = collect($livekitRoom)->firstWhere('name', $room->room_name);
            
            return $roomData['participants'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get room participants: ' . $e->getMessage());
            return [];
        }
    }

    public function roomExists(string $roomName): bool
    {
        try {
            $rooms = $this->roomServiceClient->listRooms();
            return collect($rooms)->contains('name', $roomName);
        } catch (\Exception $e) {
            Log::error('Failed to check room existence: ' . $e->getMessage());
            return false;
        }
    }
}
