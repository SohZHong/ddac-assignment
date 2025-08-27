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
use Illuminate\Validation\Rule;

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
        $forUserId = $request->integer('user_id') ?: auth()->id();
        $this->authorize('create', [EventRegistration::class, $event, $forUserId]);

        // Enforce requires_registration
        if (!$event->requires_registration) {
            return back()->withErrors(['registration' => 'This event does not require registration.']);
        }

        // Create registration or return existing
        // If event is at capacity, create (or keep) a waitlisted registration
        $defaults = [
            'status' => $event->isAtCapacity() ? 'waitlisted' : 'registered',
        ];

        $registration = EventRegistration::firstOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $forUserId,
            ],
            $defaults
        );

        if ($request->wantsJson()) {
            return response()->json(['registration' => $registration->only(['id', 'status'])]);
        }

        return redirect()->route('events.show', $event)
            ->with('success', $registration->status === 'waitlisted' ? 'Added to waitlist.' : 'Registered for event successfully.');
    }

    /**
     * Unregister the authenticated user from an event.
     */
    public function destroy(Request $request, Event $event): RedirectResponse|JsonResponse
    {
        $forUserId = $request->integer('user_id') ?: auth()->id();
        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $forUserId)
            ->first();

        $this->authorize('delete', [$registration ?? EventRegistration::class, $event, $forUserId]);

        if ($registration) {
            $registration->delete();
        }

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('events.show', $event)
            ->with('success', 'Unregistered from event successfully.');
    }

    /**
     * Update a registration's status (manager/admin on own event).
     */
    public function update(Request $request, Event $event, EventRegistration $registration): RedirectResponse|JsonResponse
    {
        $this->authorize('update', [$event, $registration]);

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['registered', 'confirmed', 'cancelled', 'waitlisted']),
            ],
        ]);

        $newStatus = $validated['status'];
        $oldStatus = $registration->status;

        // Capacity enforcement only when moving into a counting status from a non-counting one
        $countsTowardCapacity = fn(string $s) => in_array($s, ['registered', 'confirmed'], true);
        if (!$countsTowardCapacity($oldStatus) && $countsTowardCapacity($newStatus) && $event->isAtCapacity()) {
            return back()->withErrors(['registration' => 'This event has reached its capacity.']);
        }

        $registration->update(['status' => $newStatus]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $registration->status]);
        }

        return redirect()->route('events.show', $event)
            ->with('success', 'Registration status updated.');
    }
}
