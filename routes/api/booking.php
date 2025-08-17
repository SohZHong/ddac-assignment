<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Booking\BookingController;

Route::prefix('bookings')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
    Route::post('/', [BookingController::class, 'store'])
        ->name('api.booking.store');
    Route::put('/review/{booking}', [BookingController::class, 'reviewBooking'])
        ->name('api.booking.review');
    Route::put('/{booking}', [BookingController::class, 'update'])
        ->name('api.booking.update');
    Route::delete('/{booking}', [BookingController::class, 'destroy'])
        ->name('api.booking.destroy');
    });
});