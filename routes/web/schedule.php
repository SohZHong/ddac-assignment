<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Schedule\ScheduleController;

Route::prefix('schedules')->group(function () {
    // Public viewable
    Route::get('/', [ScheduleController::class, 'publicIndex'])->name('schedule.public.index');
});