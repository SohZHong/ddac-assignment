<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Blog\BlogController;

Route::prefix('blogs')->group(function () {
    // Authenticated routes — create, update, delete, restore
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/restore/{blog}', [BlogController::class, 'restore'])
            ->name('blog.restore');      // Restore soft-deleted blog
        Route::delete('/hard/{blog}', [BlogController::class, 'hardDestroy'])
            ->name('blog.harddelete');      // Hard delete blog
    });
});