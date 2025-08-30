<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConsultationReport\ConsultationReportController;

Route::prefix('reports')->group(function () {
    // Authenticated routes — create, update, delete, restore
    Route::middleware('auth:web')->group(function () {
        Route::post('/', [ConsultationReportController::class, 'store'])
            ->name('api.report.store');
    });
});