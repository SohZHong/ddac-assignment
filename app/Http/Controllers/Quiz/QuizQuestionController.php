<?php

namespace App\Http\Controllers\Quiz;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;

class QuizQuestionController extends Controller
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
    public function store(Request $request, string $id)
    {
        $quiz = Quiz::findOrFail($id);

        $this->authorize('update', $quiz);

        $request->validate([
            'question_text' => 'required|string',
            'type' => 'in:' . implode(',', [
                QuizQuestion::MCQ,
                QuizQuestion::TRUE_FALSE,
                QuizQuestion::TEXT,
            ]),
            'options' => 'nullable|array', // for MCQ only
        ]);

        $question = QuizQuestion::create([
            'quiz_id'       => $id,
            'question_text' => $request->question_text,
            'type'          => $request->type,
            'options'       => $request->options ?? null,
        ]);

        return response()->json([
            'message'       => 'Question created successfully',
            'question'      => $question
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(QuizQuestion $quizQuestion)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuizQuestion $quizQuestion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $quizId, string $questionId)
    {
        $quiz = Quiz::findOrFail($quizId);
        $quizQuestion = $quiz->questions()->findOrFail($questionId);

        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'question_text' => 'sometimes|required|string',
            'type' => 'in:' . implode(',', [
                QuizQuestion::MCQ,
                QuizQuestion::TRUE_FALSE,
                QuizQuestion::TEXT,
            ]),
            'options' => 'nullable|array',
        ]);

        $quizQuestion->update($validated);

        return response()->json([
            'message'       => 'Question updated successfully',
            'question'      => $quizQuestion
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $quizId, string $questionId)
    {
        $quiz = Quiz::findOrFail($quizId);
        $quizQuestion = $quiz->questions()->findOrFail($questionId);
        $this->authorize('delete', $quiz);

        $quizQuestion->delete();
        return response()->json([
            'message' => 'Question deleted successfully',
        ], 201);
    }
}
