<?php

use App\Http\Controllers\AssessmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('assessments')->group(function () {
    Route::get('/', [AssessmentController::class, 'index'])->name('assessment.index');
    Route::get('/history', [AssessmentController::class, 'history'])->name('assessment.history');
    Route::get('/{quiz}', [AssessmentController::class, 'show'])->name('assessment.show');
    Route::post('/{quiz}/submit', [AssessmentController::class, 'submit'])->name('assessment.submit');
    Route::get('/results/{response}', [AssessmentController::class, 'results'])->name('assessment.results');
});
