<?php

use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->group(function () {
    Route::middleware('auth:web')->group(function () {
        Route::post('/read/{id}', function($id) {
            $notification = auth()->user()->notifications()->findOrFail($id);
            $notification->markAsRead();
        })->name('api.notification.read');

        Route::post('/read-all', function() {
            auth()->user()->unreadNotifications->markAsRead();
        })->name('api.notification.read.all');
    });
});