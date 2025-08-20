<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Quiz\QuizController;
use App\Http\Controllers\Quiz\QuizQuestionController;

Route::middleware('auth:sanctum')->prefix('quizzes')->group(function () {

    Route::post('/', [QuizController::class, 'store'])
        ->name('api.quizzes.store');
    Route::post('/{quiz}/questions', [QuizQuestionController::class, 'store'])
        ->name('api.quizzes.questions.store');
    
    Route::put('/{quiz}', [QuizController::class, 'update'])
        ->name('api.quizzes.update');
    Route::put('/{quiz}/questions/{question}', [QuizQuestionController::class, 'update'])
        ->name('api.quizzes.questions.update');

    Route::delete('/{quiz}', [QuizController::class, 'destroy'])
        ->name('api.quizzes.destroy');
    Route::delete('/{quiz}/questions/{question}', [QuizQuestionController::class, 'destroy'])
        ->name('api.quizzes.questions.destroy');
});