<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Notifications\BookingNotification;
use App\Models\Booking;
use App\Models\Schedule;
use App\Notifications\BookingReviewNotification;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    /**
     * Public page for a patient to view bookings.
     */
    public function index()
    {
        $patientId = auth()->id(); // assuming logged in as patient

        $now = now();

        $upcoming = Booking::with(['schedule.healthcare:id,name'])
            ->where('patient_id', $patientId)
            ->where('start_time', '>=', $now)
            // Show confirmed or pending ones
            ->whereIn('status', [Booking::CONFIRMED, Booking::CANCELLED])
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(fn ($up) => [
                'id'            => $up->id,
                'schedule_id'   => $up->schedule_id,
                'patient_id'    => $up->patient_id,
                'start_time'    => $up->start_time,
                'end_time'      => $up->end_time,
                'status'        => $up->status,
                'healthcare'    => [
                    'id'   => $up->schedule->healthcare->id,
                    'name' => $up->schedule->healthcare->name,
                ],
            ]);

        $past = Booking::with(['schedule.healthcare:id,name'])
            ->where('patient_id', $patientId)
            ->where(function ($q) use ($now) {
                // include confirmed or pending ones that have already passed
                $q->where('start_time', '<', $now)
                ->whereIn('status', [Booking::CONFIRMED, Booking::PENDING]); 
            })
            ->orWhere(function ($q) {
                // include all cancelled, regardless of time
                $q->where('status', Booking::CANCELLED); 
            })
            ->orderBy('start_time', 'desc')
            ->paginate(10)
            ->through(fn ($p) => [
                'id'            => $p->id,
                'schedule_id'   => $p->schedule_id,
                'patient_id'    => $p->patient_id,
                'start_time'    => $p->start_time,
                'end_time'      => $p->end_time,
                'status'        => $p->status,
                'healthcare'    => [
                    'id'   => $p->schedule->healthcare->id,
                    'name' => $p->schedule->healthcare->name,
                ],
            ]);

        return Inertia::render('Booking/Index', [
            'upcoming' => $upcoming,
            'past'     => $past,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'schedule_id'  => 'required|exists:schedules,id',
            'start_time'   => 'required|date',
            'end_time'     => 'required|date|after:start_time',
        ]);

        // Get the corresponding schedule
        $schedule = Schedule::findOrFail($validated['schedule_id']);

        $this->authorize('store', [Booking::class, $schedule, $validated['start_time']]);

        $user = auth()->user();

        $booking = Booking::create([
            'schedule_id' => $validated['schedule_id'],
            'patient_id'  => $user->id,
            'start_time'  => $validated['start_time'],
            'end_time'    => $validated['end_time'],
            'status'      => Booking::PENDING,
        ]);

        // Notify healthcare professional
        $healthcare = $schedule->healthcare;
        $healthcare->notify(new BookingNotification($booking));

        return response()->json([
            'message' => 'Booking created successfully!',
            'booking' => $booking,
        ], 201);
    }

    /**
     * Update the booking status only.
     */
    public function review(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('review', $booking);

        $validated = $request->validate([
            'status' => 'in:' . implode(',', [
                Booking::PENDING,
                Booking::CONFIRMED,
                Booking::CANCELLED,
            ]),
        ]);

        // Notify user of confirmed / cancelled booking
        $patient = $booking->patient;
        $patient->notify(new BookingReviewNotification($booking));

        $booking->update($validated);

        return response()->json([
            'message' => 'Booking updated successfully!',
            'booking' => $booking,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Booking $booking)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Booking $booking)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('update', $booking);

        $validated = $request->validate([
            'start_time'   => 'required|date',
            'end_time'     => 'required|date|after:start_time',
        ]);

        $booking->update($validated);

        return response()->json([
            'message' => 'Booking updated successfully!',
            'booking' => $booking,
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        //
    }
}
