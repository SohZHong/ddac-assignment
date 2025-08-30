<?php

use App\Http\Controllers\VideoCallController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web'])->prefix('video-calls')->group(function () {
    Route::post('/create', [VideoCallController::class, 'createRoom']);
    
    Route::post('/{roomId}/join', [VideoCallController::class, 'joinRoom']);
    
    Route::post('/{roomId}/end', [VideoCallController::class, 'endCall']);
    
    Route::post('/{roomId}/signal', [VideoCallController::class, 'signal']);
    
    Route::get('/{roomId}', [VideoCallController::class, 'getCallInfo']);
});

Route::middleware(['auth:web'])->prefix('bookings')->group(function () {
    Route::get('/{bookingId}/video-call', [VideoCallController::class, 'getCallForBooking']);
});
