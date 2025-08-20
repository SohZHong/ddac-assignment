<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\QuizResponse;
use Illuminate\Http\Request;

class QuizResponseController extends Controller
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
        $$booking = Booking::findOrFail($id);

        if ($booking->status !== Booking::CONFIRMED) {
            abort(403, 'Booking must be confirmed to take the quiz.');
        }

        $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'answers' => 'required|array',
        ]);

        $response = QuizResponse::updateOrCreate(
            [
                'quiz_id' => $request->quiz_id,
                'booking_id' => $booking->id,
            ],
            [
                'answers' => $request->answers,
                'completed_at' => now(),
            ]
        );

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     */
    public function show(QuizResponse $quizResponse)
    {
        $$booking = Booking::findOrFail($id);

        if ($booking->status !== Booking::CONFIRMED) {
            abort(403, 'Booking must be confirmed to take the quiz.');
        }

        $quiz = $booking->healthcare->quizzes()->with('questions')->first(); 
        if (!$quiz) abort(404, 'No quiz assigned.');

        return response()->json($quiz);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuizResponse $quizResponse)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuizResponse $quizResponse)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuizResponse $quizResponse)
    {
        //
    }
}
