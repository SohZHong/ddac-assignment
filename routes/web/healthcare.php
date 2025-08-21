<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HealthcareDashboardController;
use App\Http\Controllers\Schedule\ScheduleController;

Route::prefix('/healthcare')->group(function () {
    Route::middleware(['auth', 'verified', 'role:healthcare_professional'])->group(function () {   
        Route::get('/', [HealthcareDashboardController::class, 'index'])
            ->name('healthcare.index');

        Route::get('/schedules', [HealthcareDashboardController::class, 'schedule'])
            ->name('healthcare.schedule.index');

        Route::get('/appointments', [HealthcareDashboardController::class, 'appointment'])
            ->name('healthcare.appointment.index');
        Route::get('/appointments/{booking}/responses/{response}', [HealthcareDashboardController::class, 'appointmentResponse'])
            ->name('healthcare.appointment.responses.show');

        Route::get('/quizzes', [HealthcareDashboardController::class, 'quizzes'])
            ->name('healthcare.quizzes.index');
        Route::get('/quizzes/{quiz}', [HealthcareDashboardController::class, 'quiz'])
            ->name('healthcare.quizzes.show');

        Route::patch('/appointments/{booking}/assess', [HealthcareDashboardController::class, 'reviewAppointmentResponse'])
            ->name('healthcare.appointment.responses.review');
    });
});