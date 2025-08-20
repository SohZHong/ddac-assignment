<?php

namespace App\Http\Controllers\Quiz;

use App\Models\Quiz;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $quizzes = Quiz::with('questions')
            ->where('healthcare_id', auth()->id())
            ->get()
            ->map(fn ($quiz) => [
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
                'active'        => $quiz->active,
        ]);
        return response()->json([
            'quizzes' => $quizzes,
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
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $quiz = Quiz::create([
            'title' => $request->title,
            'description' => $request->description,
            'healthcare_id' => auth()->id(),
        ]);

        return response()->json([
            'message'   => 'Quiz created successfully',
            'quiz'      => $quiz
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Quiz $quiz)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $quiz = Quiz::findOrFail($id);

        $this->authorize('update', $quiz);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $quiz->update($validated);

        return response()->json([
            'message'   => 'Quiz updated successfully',
            'quiz'      => $quiz
        ], 201);    
    }

    /**
     * Activate a quiz and deactivate all others for the same healthcare provider.
     */
    public function activate(Request $request, string $id)
    {
        $quiz = Quiz::findOrFail($id);

        $this->authorize('update', $quiz);

        // Deactivate other quizzes for this healthcare provider
        Quiz::where('healthcare_id', $quiz->healthcare_id)
            ->where('id', '!=', $quiz->id)
            ->update(['active' => false]);

        // Activate this quiz
        $quiz->update(['active' => true]);

        return response()->json([
            'message' => 'Quiz activated successfully',
            'quiz'    => $quiz
        ]);
    }

    /**
     * Deactivate a quiz.
     */
    public function deactivate(Request $request, string $id)
    {
        $quiz = Quiz::findOrFail($id);

        $this->authorize('update', $quiz);

        $quiz->update(['active' => false]);

        return response()->json([
            'message' => 'Quiz deactivated successfully',
            'quiz'    => $quiz
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $quiz = Quiz::findOrFail($id);

        $this->authorize('delete', $quiz);
        
        $quiz->delete();

        return response()->json([
            'message'   => 'Quiz deleted successfully',
        ], 201);        
    }
}
