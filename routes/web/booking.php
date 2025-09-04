<?php

use App\Http\Controllers\Booking\BookingController;
use Illuminate\Support\Facades\Route;

Route::prefix('bookings')->group(function () {
    Route::get('/', [BookingController::class, 'index'])->name('booking.index');
    Route::post('/', [BookingController::class, 'store'])->name('booking.store'); 
    Route::get('/{booking}/assessment', [BookingController::class, 'startAssessment'])->name('booking.assessment.index');

    Route::post('/{booking}/assessment', [BookingController::class, 'submitAssessment'])->name('booking.assessment.submit');
});