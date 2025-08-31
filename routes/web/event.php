<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Event\EventRegistrationController;
use App\Http\Controllers\Event\EventAttendanceController;

Route::middleware(['auth', 'role:health_campaign_manager,system_admin'])
    ->group(function () {
        // Static routes must come before resource to avoid conflicts with events/{event}
        Route::get('events/calendar', function () {
            return Inertia::render('Events/Calendar');
        })->name('events.calendar');

        Route::get('events-feed', [EventController::class, 'feed'])->name('events.feed');

        Route::resource('events', EventController::class)
            ->whereNumber('event');
    });

// Livestream route - moved outside role middleware for testing
Route::get('events/{event}/livestream', function ($event) {
    $eventModel = \App\Models\Event::findOrFail($event);
    $livestreamRoom = $eventModel->livestreamRoom;
    
    if (!$livestreamRoom) {
        abort(404, 'Livestream not available for this event');
    }
    
    return Inertia::render('Events/Livestream', [
        'roomId' => $livestreamRoom->id,
        'eventTitle' => $eventModel->title,
    ]);
})->name('events.livestream')->middleware(['auth']);

// Registration routes (auth only)
Route::middleware(['auth'])->group(function () {
    Route::get('events/{event}/registrations', [EventRegistrationController::class, 'index'])
        ->name('events.registrations.index');
    Route::post('events/{event}/registrations', [EventRegistrationController::class, 'store'])
        ->name('events.registrations.store');
    Route::patch('events/{event}/registrations/{registration}', [EventRegistrationController::class, 'update'])
        ->name('events.registrations.update');
    Route::delete('events/{event}/registrations', [EventRegistrationController::class, 'destroy'])
        ->name('events.registrations.destroy');

    // CSV exports (streamed)
    Route::get('events/{event}/registrations.csv', [EventRegistrationController::class, 'exportRegistrations'])
        ->name('events.registrations.export');
    Route::get('events/{event}/attendances.csv', [EventRegistrationController::class, 'exportAttendances'])
        ->name('events.attendances.export');

    // Attendance
    Route::get('events/{event}/attendances', [EventAttendanceController::class, 'index'])
        ->name('events.attendances.index');
    Route::post('events/{event}/attendances', [EventAttendanceController::class, 'store'])
        ->name('events.attendances.store');
    Route::delete('events/{event}/attendances', [EventAttendanceController::class, 'destroy'])
        ->name('events.attendances.destroy');
});
