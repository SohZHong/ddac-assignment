<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HealthcareDashboardController;

Route::prefix('/healthcare')->group(function () {
    Route::middleware(['auth', 'verified', 'role:healthcare_professional'])->group(function () {   
        Route::get('/', [HealthcareDashboardController::class, 'index'])
            ->name('healthcare.index');

        // Patient 
        Route::get('/patients', [HealthcareDashboardController::class, 'patient'])
            ->name('healthcare.patient.index');

        // Blog
        Route::get('/blogs', [HealthcareDashboardController::class, 'blog'])
            ->name('healthcare.blog.index');
        Route::get('/blogs/create', [HealthcareDashboardController::class, 'createBlog'])
            ->name('healthcare.blog.create');      // Page to display create form
        Route::get('/blogs/{blog}/edit', [HealthcareDashboardController::class, 'editBlog'])
            ->name('healthcare.blog.edit');      // Page to display edit form
            
        Route::post('/blogs/store', [HealthcareDashboardController::class, 'storeBlog'])
            ->name('healthcare.blog.store');

        Route::put('/blogs/{blog}/update', [HealthcareDashboardController::class, 'updateBlog'])
            ->name('healthcare.blog.update');

        // Schedule
        Route::get('/schedules', [HealthcareDashboardController::class, 'schedule'])
            ->name('healthcare.schedule.index');

        // Appointments
        Route::get('/appointments', [HealthcareDashboardController::class, 'appointment'])
            ->name('healthcare.appointment.index');
        Route::get('/appointments/{booking}/responses/{response}', [HealthcareDashboardController::class, 'appointmentResponse'])
            ->name('healthcare.appointment.responses.show');

        Route::patch('/appointments/{booking}/assess', [HealthcareDashboardController::class, 'reviewAppointmentResponse'])
            ->name('healthcare.appointment.responses.review');

        // Quiz
        Route::get('/quizzes', [HealthcareDashboardController::class, 'quizzes'])
            ->name('healthcare.quizzes.index');
        Route::get('/quizzes/{quiz}', [HealthcareDashboardController::class, 'quiz'])
            ->name('healthcare.quizzes.show');

    });
});