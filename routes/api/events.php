<?php

use App\Http\Controllers\Event\EventController;
use Illuminate\Support\Facades\Route;

// Public routes for viewing published events (authentication required, but accessible to all users)
Route::middleware(['auth:sanctum,web'])->group(function () {
    Route::get('/public/events', [EventController::class, 'publicIndex']);
    Route::get('/public/events/{event}', [EventController::class, 'publicShow']);
    Route::get('/public/events-feed', [EventController::class, 'publicFeed']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/events', [EventController::class, 'index']);
});
