<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HealthcareDashboardController;
use App\Http\Controllers\Schedule\ScheduleController;

Route::prefix('/')->group(function () {
    Route::middleware(['auth', 'verified', 'role:healthcare_professional'])->group(function () {   
        Route::get('/healthcare', [HealthcareDashboardController::class, 'index'])
            ->name('healthcare.index');
        Route::get('/appointments', [HealthcareDashboardController::class, 'appointment'])
            ->name('healthcare.appointment.index');
        Route::get('/quizzes', [HealthcareDashboardController::class, 'quizzes'])
            ->name('healthcare.quizzes.index');
        Route::get('/quizzes/{quiz}', [HealthcareDashboardController::class, 'quiz'])
            ->name('healthcare.quizzes.show');
    });
});