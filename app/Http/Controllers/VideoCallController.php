<?php

namespace App\Http\Controllers;

use App\Models\VideoCall;
use App\Models\Booking;
use App\Events\VideoCallCreated;
use App\Events\ParticipantJoined;
use App\Events\VideoCallEnded;
use App\Events\WebRTCSignal;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VideoCallController extends Controller
{
    public function show(string $roomId)
    {
        $videoCall = VideoCall::where('room_id', $roomId)
            ->with(['doctor', 'patient', 'booking'])
            ->first();

        if (!$videoCall) {
            abort(404, 'Video call not found');
        }

        if (Auth::id() !== $videoCall->doctor_id && Auth::id() !== $videoCall->patient_id) {
            abort(403, 'Unauthorized');
        }

        return Inertia::render('VideoCall/Room', [
            'roomId' => $videoCall->room_id,
            'doctor' => [
                'id' => $videoCall->doctor->id,
                'name' => $videoCall->doctor->name,
            ],
            'patient' => [
                'id' => $videoCall->patient->id,
                'name' => $videoCall->patient->name,
            ],
            'currentUser' => [
                'id' => Auth::id(),
                'name' => Auth::user()->name,
                'role' => Auth::id() === $videoCall->doctor_id ? 'doctor' : 'patient',
            ],
        ]);
    }

    public function createRoom(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $booking = Booking::with(['patient', 'schedule.healthcare'])->findOrFail($request->booking_id);
        
        if (Auth::id() !== $booking->schedule->healthcare_id) {
            abort(403, 'Unauthorized to create room for this booking');
        }

        $existingCall = VideoCall::where('booking_id', $booking->id)
            ->whereIn('status', [VideoCall::STATUS_WAITING, VideoCall::STATUS_ACTIVE])
            ->first();

        if ($existingCall) {
            return response()->json([
                'room_id' => $existingCall->room_id,
                'status' => $existingCall->status,
                'message' => 'Video call room already exists'
            ]);
        }

        $videoCall = VideoCall::create([
            'room_id' => VideoCall::generateRoomId(),
            'booking_id' => $booking->id,
            'doctor_id' => $booking->schedule->healthcare_id,
            'patient_id' => $booking->patient_id,
            'status' => VideoCall::STATUS_WAITING,
        ]);

        broadcast(new \App\Events\VideoCallCreated($videoCall))->toOthers();

        return response()->json([
            'room_id' => $videoCall->room_id,
            'status' => $videoCall->status,
            'message' => 'Video call room created successfully'
        ]);
    }

    public function joinRoom(Request $request, $roomId)
    {
        $videoCall = VideoCall::where('room_id', $roomId)
            ->with(['booking', 'doctor', 'patient'])
            ->firstOrFail();

        $user = Auth::user();
        
        if ($user->id !== $videoCall->doctor_id && $user->id !== $videoCall->patient_id) {
            abort(403, 'Unauthorized to join this video call');
        }

        $participants = $videoCall->participants ?? [];
        $participants[$user->id] = [
            'name' => $user->name,
            'joined_at' => now()->toISOString(),
            'role' => $user->id === $videoCall->doctor_id ? 'doctor' : 'patient'
        ];

        $videoCall->update(['participants' => $participants]);

        if ($videoCall->status === VideoCall::STATUS_WAITING) {
            $videoCall->update([
                'status' => VideoCall::STATUS_ACTIVE,
                'started_at' => now()
            ]);
        }

        broadcast(new \App\Events\ParticipantJoined($videoCall, $user))->toOthers();

        return Inertia::render('VideoCall/Room', [
            'videoCall' => $videoCall,
            'roomId' => $roomId,
            'isDoctor' => $user->id === $videoCall->doctor_id,
            'otherParticipant' => $user->id === $videoCall->doctor_id ? $videoCall->patient : $videoCall->doctor,
        ]);
    }

    public function endCall(Request $request, $roomId)
    {
        $videoCall = VideoCall::where('room_id', $roomId)->firstOrFail();
        
        $user = Auth::user();
        
        if ($user->id !== $videoCall->doctor_id && $user->id !== $videoCall->patient_id) {
            abort(403, 'Unauthorized to end this video call');
        }

        $videoCall->endCall();

        broadcast(new \App\Events\VideoCallEnded($videoCall))->toOthers();

        return response()->json([
            'message' => 'Video call ended successfully',
            'duration' => $videoCall->duration_seconds
        ]);
    }

    public function signal(Request $request, string $roomId)
    {
        $request->validate([
            'signal' => 'required|array',
            'to_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $videoCall = VideoCall::where('room_id', $roomId)
            ->where('status', VideoCall::STATUS_ACTIVE)
            ->first();

        if (!$videoCall) {
            return response()->json(['error' => 'Video call not found or not active'], 404);
        }

        if (Auth::id() !== $videoCall->doctor_id && Auth::id() !== $videoCall->patient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        broadcast(new WebRTCSignal(
            $roomId,
            Auth::id(),
            $request->to_user_id,
            $request->signal
        ));

        return response()->json(['success' => true]);
    }    /**
     * Get call information
     */
    public function getCallInfo(string $roomId)
    {
        $videoCall = VideoCall::where('room_id', $roomId)
            ->with(['doctor', 'patient', 'booking'])
            ->first();

        if (!$videoCall) {
            return response()->json(['error' => 'Video call not found'], 404);
        }

        // Check if user is authorized to view this call
        if (Auth::id() !== $videoCall->doctor_id && Auth::id() !== $videoCall->patient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'room_id' => $videoCall->room_id,
            'status' => $videoCall->status,
            'doctor' => [
                'id' => $videoCall->doctor->id,
                'name' => $videoCall->doctor->name,
            ],
            'patient' => [
                'id' => $videoCall->patient->id,
                'name' => $videoCall->patient->name,
            ],
            'started_at' => $videoCall->started_at,
            'ended_at' => $videoCall->ended_at,
            'call_duration' => $videoCall->call_duration,
        ]);
    }

    /**
     * Get video call for a specific booking
     */
    public function getCallForBooking(int $bookingId)
    {
        $booking = Booking::with(['schedule.healthcare'])->findOrFail($bookingId);
        
        // Check if user is authorized to view this booking (either doctor or patient)
        if (Auth::id() !== $booking->schedule->healthcare_id && Auth::id() !== $booking->patient_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $videoCall = VideoCall::where('booking_id', $bookingId)
            ->where('status', '!=', VideoCall::STATUS_ENDED)
            ->first();

        if (!$videoCall) {
            return response()->json(['video_call' => null]);
        }

        return response()->json([
            'video_call' => [
                'room_id' => $videoCall->room_id,
                'status' => $videoCall->status,
                'started_at' => $videoCall->started_at,
            ]
        ]);
    }
}
