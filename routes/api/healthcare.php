<?php

use App\Http\Controllers\Healthcare\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->prefix('healthcare')->group(function () {
    Route::get('/patients/{patientId}/latest-booking', [PatientController::class, 'getLatestBooking']);
});
