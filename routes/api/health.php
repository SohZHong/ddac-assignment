<?php

use App\Http\Controllers\HealthProgressController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->prefix('health')->group(function () {
    Route::get('/progress', [HealthProgressController::class, 'index'])->name('health.progress');
    Route::post('/metrics', [HealthProgressController::class, 'storeMetric'])->name('health.metrics.store');
    Route::patch('/recommendations/{recommendation}', [HealthProgressController::class, 'updateRecommendationStatus'])->name('health.recommendations.update');
});
