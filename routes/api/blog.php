<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Blog\BlogController;

Route::prefix('blogs')->group(function () {
    // Authenticated routes — create, update, delete, restore
    Route::middleware('auth:web')->group(function () {
        Route::patch('/{blog}/publish', [BlogController::class, 'publish'])
            ->name('api.blog.update.publish');      // Publish blog
        Route::patch('/{blog}/draft', [BlogController::class, 'draft'])
            ->name('api.blog.update.draft');      // Make blog a draft

        Route::post('/restore/{blog}', [BlogController::class, 'restore'])
            ->name('api.blog.restore');      // Restore soft-deleted blog
        
        Route::delete('/{blog}/delete', [BlogController::class, 'destroy'])
            ->name('api.blog.delete.soft');      // Soft deleted blog

        Route::delete('/{blog}/hard-delete', [BlogController::class, 'hardDestroy'])
            ->name('api.blog.delete.hard');      // Hard delete blog
    });
});