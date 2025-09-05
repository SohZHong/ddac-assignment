<?php

namespace App\Http\Controllers;

use App\Models\LivekitRoom;
use App\Services\LivekitTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LivekitTokenController extends Controller
{
    public function __construct(
        private LivekitTokenService $tokenService
    ) {}

    public function generateToken(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:livekit_rooms,id',
        ]);

        $room = LivekitRoom::findOrFail($request->room_id);
        
        // Check if user has access to this room
        if (!$this->canAccessRoom($room)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $participantIdentity = 'user-' . Auth::id() . '-' . uniqid();
        $token = $this->tokenService->generateToken(Auth::user(), $room, $participantIdentity);

        return response()->json([
            'token' => $token,
            'room_name' => $room->room_name,
            'participant_identity' => $participantIdentity,
        ]);
    }

    private function canAccessRoom(LivekitRoom $room): bool
    {
        // Room creator can always access
        if ($room->created_by === Auth::id()) {
            return true;
        }

        // Check if user is registered for the event
        $event = $room->event;
        return $event->registrations()->where('user_id', Auth::id())->exists();
    }
}
