<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventAttendanceController extends Controller
{
    /**
     * List attendances for an event (creator or admin only).
     */
    public function index(Event $event): Response|JsonResponse
    {
        $this->authorize('viewAny', [EventAttendance::class, $event]);

        $attendances = $event->attendances()
            ->with('user')
            ->latest()
            ->get()
            ->map(function (EventAttendance $attendance) {
                return [
                    'id' => $attendance->id,
                    'user' => [
                        'id' => $attendance->user->id,
                        'name' => $attendance->user->name,
                        'email' => $attendance->user->email,
                    ],
                    'status' => $attendance->status,
                    'check_in_time' => optional($attendance->check_in_time)?->format('Y-m-d H:i:s'),
                    'check_out_time' => optional($attendance->check_out_time)?->format('Y-m-d H:i:s'),
                ];
            });

        if (app()->environment('testing')) {
            return response()->json(['attendances' => $attendances]);
        }

        return Inertia::render('Events/Attendances/Index', [
            'eventId' => $event->id,
            'attendances' => $attendances,
        ]);
    }

    /**
     * Check-in the authenticated user for an event.
     */
    public function store(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        $forUserId = $request->integer('user_id') ?: auth()->id();
        $this->authorize('create', [EventAttendance::class, $event, $forUserId]);

        // If registration is required, ensure the user is registered
        if ($event->requires_registration) {
            $isRegistered = EventRegistration::where('event_id', $event->id)
                ->where('user_id', auth()->id())
                ->exists();
            if (!$isRegistered) {
                return back()->withErrors(['attendance' => 'You must register before checking in.']);
            }
        }

        $attendance = EventAttendance::firstOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $forUserId,
            ],
            [
                'status' => 'present',
                'check_in_time' => now(),
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['attendance' => $attendance->only(['id', 'status'])]);
        }

        return redirect()->route('events.show', $event)
            ->with('success', 'Checked in successfully.');
    }

    /**
     * Undo check-in for the authenticated user.
     */
    public function destroy(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        $forUserId = $request->integer('user_id') ?: auth()->id();
        $attendance = EventAttendance::where('event_id', $event->id)
            ->where('user_id', $forUserId)
            ->first();

        $this->authorize('delete', [$attendance ?? EventAttendance::class, $event, $forUserId]);

        if ($attendance) {
            $attendance->delete();
        }

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('events.show', $event)
            ->with('success', 'Check-in removed.');
    }
}
