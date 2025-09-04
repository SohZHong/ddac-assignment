<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Booking\BookingController;

Route::prefix('bookings')->group(function () {
    Route::middleware('auth:web')->group(function () {
    Route::post('/', [BookingController::class, 'store'])
        ->name('api.booking.store');

    Route::patch('/approve/{booking}', [BookingController::class, 'approve'])
        ->name('api.booking.approve');
    Route::patch('/decline/{booking}', [BookingController::class, 'decline'])
        ->name('api.booking.decline');
    Route::patch('/cancel/{booking}', [BookingController::class, 'cancelByPatient'])
        ->name('api.booking.cancel');
    
    Route::put('/{booking}', [BookingController::class, 'update'])
        ->name('api.booking.update');
    Route::delete('/{booking}', [BookingController::class, 'destroy'])
        ->name('api.booking.destroy');
    });
});