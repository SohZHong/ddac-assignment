<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\HealthcareDashboardController;
use App\Http\Controllers\Schedule\ScheduleController;

Route::prefix('healthcare')->group(function () {
    Route::middleware(['auth', 'verified', 'role:healthcare_professional'])->group(function () {   
        Route::get('/', [HealthcareDashboardController::class, 'index'])
            ->name('healthcare.dashboard');
    });
});