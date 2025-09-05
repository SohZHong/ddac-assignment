<?php

use App\Http\Controllers\LivekitController;
use App\Http\Controllers\LivekitTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('livekit')->middleware(['auth:sanctum'])->group(function () {
    // Token generation
    Route::post('/token', [LivekitTokenController::class, 'generateToken']);
    
    // Room management
    Route::post('/rooms', [LivekitController::class, 'createRoom']);
    Route::get('/rooms/{room}', [LivekitController::class, 'getRoom']);
    Route::post('/rooms/{room}/join', [LivekitController::class, 'joinRoom']);
    Route::post('/rooms/{room}/leave', [LivekitController::class, 'leaveRoom']);
    Route::post('/rooms/{room}/start', [LivekitController::class, 'startRoom']);
    Route::post('/rooms/{room}/end', [LivekitController::class, 'endRoom']);
    Route::get('/rooms/{room}/participants', [LivekitController::class, 'getParticipants']);

    // Chat history & persistence
    Route::get('/rooms/{room}/chat', [LivekitController::class, 'getChat']);
    Route::post('/rooms/{room}/chat', [LivekitController::class, 'postChat']);
});
