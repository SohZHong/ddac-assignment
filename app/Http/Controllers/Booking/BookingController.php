<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Notifications\BookingNotification;
use App\Models\Booking;
use App\Models\Quiz;
use App\Models\QuizResponse;
use App\Models\Schedule;
use App\Notifications\BookingReviewNotification;
use App\Notifications\HealthcareCompleteAssessmentNotification;
use App\Notifications\PatientCancelBookingNotification;
use App\Notifications\PatientCompleteAssessmentNotification;
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
            ->get()
            ->map(fn ($p) => [
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

    public function startAssessment(string $bookingId) {
        $booking = Booking::with(['schedule.healthcare'])
            ->findOrFail($bookingId);

        $this->authorize('assessment', $booking);

        $bookingData = [
            'id'          => $booking->id,
            'schedule_id' => $booking->schedule_id,
            'patient_id'  => $booking->patient_id,
            'start_time'  => $booking->start_time,
            'end_time'    => $booking->end_time,
            'status'      => $booking->status,
            'healthcare'  => [
                'id'   => $booking->schedule->healthcare->id,
                'name' => $booking->schedule->healthcare->name,
            ],
        ];

        $quiz = Quiz::with('questions')
            ->where('healthcare_id', $booking->schedule->healthcare_id)
            ->where('active', true)
            ->firstOrFail();

        $response = QuizResponse::where('quiz_id', $quiz->id)
            ->where('booking_id', $booking->id)
            ->first();
            
        $quizData = [
            'id'            => $quiz->id,
            'healthcare_id' => $quiz->healthcare_id,
            'title'         => $quiz->title,
            'description'   => $quiz->description,
            'questions'     => $quiz->questions->map(fn ($q) => [
                'id'            => $q->id,
                'quiz_id'       => $q->quiz_id,
                'question_text' => $q->question_text,
                'type'          => $q->type,
                'options'       => $q->options,
            ]),
            'active'        => $quiz->active
        ];

        return Inertia::render('Booking/Assessment', [
            'booking' => $bookingData,
            'quiz'    => $quizData,
            'response'=> $response
        ]);
    }

    public function submitAssessment(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);

        $this->authorize('assessment', $booking);

        $validated = $request->validate([
            'quiz_id'  => 'required|exists:quizzes,id',
            'answers'  => 'required|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.answer'      => 'nullable|string',
        ]);

        QuizResponse::updateOrCreate(
            [
                'quiz_id'    => $validated['quiz_id'],
                'booking_id' => $booking->id,
            ],
            [
                'answers'      => $validated['answers'],
                'completed_at' => now(),
            ]
        );

        auth()->user()->notify(new HealthcareCompleteAssessmentNotification($booking));
        $booking->healthcare->notify(new PatientCompleteAssessmentNotification($booking));

        return redirect()->route('booking.index')->with('success', 'Assessment submitted successfully.');
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
     * Approve the booking.
     */
    public function approve(string $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('approve', $booking);

        $booking->update([
            'status' => Booking::CONFIRMED
        ]);

        // Notify user of confirmed  booking
        $patient = $booking->patient;
        $patient->notify(new BookingReviewNotification($booking));

        return response()->json([
            'message' => 'Booking approved successfully!',
            'booking' => $booking,
        ], 201);
    }

    /**
     * Decline the booking.
     */
    public function decline(string $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('decline', $booking);

        $booking->update([
            'status' => Booking::CANCELLED
        ]);

        // Notify user of confirmed  booking
        $patient = $booking->patient;
        $patient->notify(new BookingReviewNotification($booking));

        return response()->json([
            'message' => 'Booking declined successfully!',
            'booking' => $booking,
        ], 201);
    }

    /**
     * Cancel the booking by patient.
     */
    public function cancelByPatient(string $id)
    {
        $booking = Booking::findOrFail($id);

        $this->authorize('cancelByPatient', $booking);

        $booking->update([
            'status' => Booking::CANCELLED,
        ]);

        // Notify healthcare of cancelled booking
        $healthcare = $booking->schedule->healthcare;
        $healthcare->notify(new PatientCancelBookingNotification($booking));

        return response()->json([
            'message' => 'Booking cancelled successfully!',
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
