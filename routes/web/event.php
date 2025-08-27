<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Event\EventRegistrationController;
use App\Http\Controllers\Event\EventAttendanceController;

Route::middleware(['auth', 'role:health_campaign_manager,system_admin'])
    ->group(function () {
        Route::resource('events', EventController::class);
    });

// Registration routes (auth only)
Route::middleware(['auth'])->group(function () {
    Route::get('events/{event}/registrations', [EventRegistrationController::class, 'index'])
        ->name('events.registrations.index');
    Route::post('events/{event}/registrations', [EventRegistrationController::class, 'store'])
        ->name('events.registrations.store');
    Route::delete('events/{event}/registrations', [EventRegistrationController::class, 'destroy'])
        ->name('events.registrations.destroy');

    // Attendance
    Route::get('events/{event}/attendances', [EventAttendanceController::class, 'index'])
        ->name('events.attendances.index');
    Route::post('events/{event}/attendances', [EventAttendanceController::class, 'store'])
        ->name('events.attendances.store');
    Route::delete('events/{event}/attendances', [EventAttendanceController::class, 'destroy'])
        ->name('events.attendances.destroy');
});
