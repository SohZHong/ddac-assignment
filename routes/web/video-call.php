<?php

use App\Http\Controllers\VideoCallController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth'])->prefix('video-call')->group(function () {
    // Demo page for testing video calls
    Route::get('/demo', function () {
        return Inertia::render('VideoCall/Demo');
    })->name('video-call.demo');
    
    // Display video call room
    Route::get('/{roomId}', [VideoCallController::class, 'show'])->name('video-call.show');
});
