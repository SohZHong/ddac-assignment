<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Schedule\ScheduleController;

Route::middleware('auth:sanctum')->prefix('schedule')->group(function () {
    Route::post('/', [ScheduleController::class, 'store'])->name('api.schedule.store');
    Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('api.schedule.update');
    Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('api.schedule.destroy');
});

// Additional route for fetching schedules by healthcare provider
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/schedules/healthcare/{healthcareId}', [ScheduleController::class, 'getByHealthcare']);
});