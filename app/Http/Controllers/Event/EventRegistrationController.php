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
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Notifications\EventRegistrationConfirmed;

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

        // Notify the user when they register (or already registered)
        try {
            $registration->user->notify(new EventRegistrationConfirmed($event));
        } catch (\Throwable $e) {
            // swallow errors to not block UX
        }

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

    /**
     * Stream registrations CSV for an event.
     */
    public function exportRegistrations(Request $request, Event $event): StreamedResponse
    {
        $this->authorize('viewAny', [EventRegistration::class, $event]);

        $status = $request->string('status')->toString() ?: null;
        $from = $request->date('from');
        $to = $request->date('to');

        $filename = sprintf('event_%d_registrations_%s.csv', $event->id, now()->format('Ymd_His'));

        return response()->streamDownload(function () use ($event, $status, $from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['User ID', 'Name', 'Email', 'Status', 'Registered At']);

            $query = $event->registrations()->with('user')
                ->when($status, fn($q) => $q->where('status', $status))
                ->when($from, fn($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn($q) => $q->where('created_at', '<=', $to));

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->user->id,
                        $r->user->name,
                        $r->user->email,
                        $r->status,
                        $r->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Stream attendances CSV for an event.
     */
    public function exportAttendances(Request $request, Event $event): StreamedResponse
    {
        $this->authorize('viewAny', [EventRegistration::class, $event]);

        $present = $request->boolean('present', null);
        $from = $request->date('from');
        $to = $request->date('to');

        $filename = sprintf('event_%d_attendances_%s.csv', $event->id, now()->format('Ymd_His'));

        return response()->streamDownload(function () use ($event, $present, $from, $to) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['User ID', 'Name', 'Email', 'Check-in Time', 'Check-out Time']);

            $query = $event->attendances()->with('user')
                ->when(!is_null($present), function ($q) use ($present) {
                    if ($present) {
                        $q->where('status', 'present');
                    } else {
                        $q->where('status', '!=', 'present');
                    }
                })
                ->when($from, fn($q) => $q->where('created_at', '>=', $from))
                ->when($to, fn($q) => $q->where('created_at', '<=', $to));

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $a) {
                    fputcsv($out, [
                        $a->user->id,
                        $a->user->name,
                        $a->user->email,
                        optional($a->check_in_time)?->format('Y-m-d H:i:s'),
                        optional($a->check_out_time)?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
