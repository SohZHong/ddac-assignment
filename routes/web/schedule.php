<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Schedule\ScheduleController;

Route::prefix('schedules')->group(function () {
    Route::get('/', [ScheduleController::class, 'index'])->name('schedule.index');
});