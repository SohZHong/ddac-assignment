<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Notifications\BookingNotification;
use App\Models\Booking;
use App\Models\Schedule;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    public function reviewBooking(Request $request, string $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('reviewBooking', $booking);

        $validated = $request->validate([
            'status' => 'in:' . implode(',', [
                Booking::PENDING,
                Booking::CONFIRMED,
                Booking::CANCELLED,
            ]),
        ]);

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
