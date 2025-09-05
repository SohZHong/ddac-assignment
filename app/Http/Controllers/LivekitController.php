<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\LivekitRoom;
use App\Models\LivekitParticipant;
use App\Models\ChatMessage;
use App\Services\LivekitRoomService;
use App\Services\LivekitTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LivekitController extends Controller
{
    public function __construct(
        private LivekitRoomService $roomService,
        private LivekitTokenService $tokenService
    ) {}

    public function createRoom(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
            'max_participants' => 'integer|min:1|max:1000',
        ]);

        $event = Event::findOrFail($request->event_id);
        
        // Check if user is the event creator or has permission
        if ($event->created_by !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if room already exists for this event
        $existingRoom = LivekitRoom::where('event_id', $event->id)->first();
        if ($existingRoom) {
            return response()->json(['error' => 'Room already exists for this event'], 400);
        }

        $room = $this->roomService->createRoom(
            $event,
            Auth::user(),
            $request->get('max_participants', 100)
        );

        return response()->json([
            'message' => 'Room created successfully',
            'room' => $room->load('event'),
        ], 201);
    }

    public function getRoom(LivekitRoom $room): JsonResponse
    {
        // Check if user has access to this room
        if (!$this->canAccessRoom($room)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'room' => $room->load(['event', 'creator', 'participants.user']),
        ]);
    }

    public function joinRoom(Request $request, LivekitRoom $room): JsonResponse
    {
        Log::info('Join room request', [
            'room_id' => $room->id,
            'room_status' => $room->status,
            'user_id' => Auth::id(),
            'event_id' => $room->event_id
        ]);

        // Check if user has access to this room
        if (!$this->canAccessRoom($room)) {
            Log::warning('User unauthorized to join room', [
                'room_id' => $room->id,
                'user_id' => Auth::id()
            ]);
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if room is scheduled or live (allow joining both)
        if ($room->status !== 'scheduled' && $room->status !== 'live') {
            Log::warning('Room not available for joining', [
                'room_id' => $room->id,
                'room_status' => $room->status
            ]);
            return response()->json(['error' => 'Room is not available for joining'], 400);
        }

        $participantIdentity = 'user-' . Auth::id() . '-' . Str::random(8);
        
        // Create or update participant record
        LivekitParticipant::updateOrCreate(
            [
                'room_id' => $room->id,
                'user_id' => Auth::id(),
            ],
            [
                'participant_identity' => $participantIdentity,
                'left_at' => null,
            ]
        );

        $token = $this->tokenService->generateToken(Auth::user(), $room, $participantIdentity);

        return response()->json([
            'token' => $token,
            'room_name' => $room->room_name,
            'participant_identity' => $participantIdentity,
            'ws_url' => config('services.livekit.url'),
            'can_publish' => $room->created_by === Auth::id(),
        ]);
    }

    public function leaveRoom(Request $request, LivekitRoom $room): JsonResponse
    {
        $participant = LivekitParticipant::where('room_id', $room->id)
            ->where('user_id', Auth::id())
            ->whereNull('left_at')
            ->first();

        if ($participant) {
            $participant->leave();
        }

        return response()->json(['message' => 'Left room successfully']);
    }

    public function startRoom(LivekitRoom $room): JsonResponse
    {
        // Check if user is the room creator
        if ($room->created_by !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->roomService->startRoom($room);

        return response()->json([
            'message' => 'Room started successfully',
            'room' => $room->fresh(),
        ]);
    }

    public function endRoom(LivekitRoom $room): JsonResponse
    {
        // Check if user is the room creator
        if ($room->created_by !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->roomService->endRoom($room);

        return response()->json([
            'message' => 'Room ended successfully',
            'room' => $room->fresh(),
        ]);
    }

    public function getParticipants(LivekitRoom $room): JsonResponse
    {
        // Check if user has access to this room
        if (!$this->canAccessRoom($room)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $participants = $room->participants()
            ->with('user')
            ->whereNull('left_at')
            ->get();

        return response()->json([
            'participants' => $participants,
        ]);
    }

    public function getChat(LivekitRoom $room): JsonResponse
    {
        if (!$this->canAccessRoom($room)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = ChatMessage::where('room_id', $room->id)
            ->orderBy('sent_at', 'asc')
            ->limit(200)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'sender_identity' => $m->sender_identity,
                'text' => $m->text,
                'sent_at' => $m->sent_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function postChat(Request $request, LivekitRoom $room): JsonResponse
    {
        if (!$this->canAccessRoom($room)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'text' => 'required|string|max:4000',
            'participant_identity' => 'nullable|string|max:255',
        ]);

        $message = ChatMessage::create([
            'room_id' => $room->id,
            'sender_id' => Auth::id(),
            'sender_identity' => $validated['participant_identity'] ?? null,
            'text' => $validated['text'],
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_identity' => $message->sender_identity,
                'text' => $message->text,
                'sent_at' => $message->sent_at?->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }

    private function canAccessRoom(LivekitRoom $room): bool
    {
        Log::info('Checking room access', [
            'room_id' => $room->id,
            'user_id' => Auth::id(),
            'room_creator' => $room->created_by,
            'event_id' => $room->event_id
        ]);

        // Room creator can always access
        if ($room->created_by === Auth::id()) {
            Log::info('User is room creator');
            return true;
        }

        // For public events, all authenticated users can join livestreams
        $event = $room->event;
        if ($event->status === 'published') {
            Log::info('Event is published, allowing all authenticated users');
            return true;
        }

        // For non-public events, check if user is registered for the event
        $isRegistered = $event->registrations()->where('user_id', Auth::id())->exists();
        
        Log::info('User event registration check', [
            'user_id' => Auth::id(),
            'event_id' => $event->id,
            'is_registered' => $isRegistered
        ]);
        
        return $isRegistered;
    }
}
