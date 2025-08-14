<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Blog\BlogController;

Route::prefix('blogs')->group(function () {
    // Public routes
    Route::get('/', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/{blog}', [BlogController::class, 'show'])->name('blog.show');

    // Authenticated routes — create, update, delete, restore
    Route::middleware('auth')->group(function () {
        Route::post('/', [BlogController::class, 'store'])
            ->name('blog.create');       // Create blog
        Route::put('/{blog}', [BlogController::class, 'update'])
            ->name('blog.update');       // Update blog
        Route::delete('/{blog}', [BlogController::class, 'destroy'])
            ->name('blog.softdelete');   // Soft delete blog
        Route::post('/restore/{blog}', [BlogController::class, 'restore'])
            ->name('blog.restore');      // Restore soft-deleted blog
        Route::delete('/hard/{blog}', [BlogController::class, 'hardDestroy'])
            ->name('blog.harddelete');      // Hard delete blog
    });
});