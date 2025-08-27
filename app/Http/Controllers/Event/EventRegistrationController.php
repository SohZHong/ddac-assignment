<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class EventRegistrationController extends Controller
{
    /**
     * List registrations for an event (creator or admin only).
     */
    public function index(Event $event): Response|JsonResponse
    {
        $this->authorize('viewAny', [EventRegistration::class, $event]);

        $registrations = $event->registrations()
            ->with('user')
            ->latest()
            ->get()
            ->map(function (EventRegistration $registration) {
                return [
                    'id' => $registration->id,
                    'user' => [
                        'id' => $registration->user->id,
                        'name' => $registration->user->name,
                        'email' => $registration->user->email,
                    ],
                    'status' => $registration->status,
                    'notes' => $registration->notes,
                    'created_at' => $registration->created_at->format('Y-m-d H:i:s'),
                ];
            });

        if (app()->environment('testing')) {
            return response()->json(['registrations' => $registrations]);
        }

        return Inertia::render('Events/Registrations/Index', [
            'eventId' => $event->id,
            'registrations' => $registrations,
        ]);
    }

    /**
     * Register the authenticated user for an event.
     */
    public function store(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        $this->authorize('create', [EventRegistration::class, $event]);

        // Enforce requires_registration
        if (!$event->requires_registration) {
            return back()->withErrors(['registration' => 'This event does not require registration.']);
        }

        // Enforce capacity
        if ($event->isAtCapacity()) {
            return back()->withErrors(['registration' => 'This event has reached its capacity.']);
        }

        // Create registration or return existing
        $registration = EventRegistration::firstOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => auth()->id(),
            ],
            [
                'status' => 'registered',
            ]
        );

        if ($request->wantsJson()) {
            return response()->json(['registration' => $registration->only(['id', 'status'])]);
        }

        return redirect()->route('events.show', $event)
            ->with('success', 'Registered for event successfully.');
    }

    /**
     * Unregister the authenticated user from an event.
     */
    public function destroy(Event $event): RedirectResponse|JsonResponse
    {
        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', auth()->id())
            ->first();

        $this->authorize('delete', [$registration ?? EventRegistration::class, $event]);

        if ($registration) {
            $registration->delete();
        }

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('events.show', $event)
            ->with('success', 'Unregistered from event successfully.');
    }
}
