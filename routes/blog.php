<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;

Route::prefix('blogs')->group(function () {

    // Public (guest) routes — only viewing
    Route::middleware('guest')->group(function () {
        Route::get('/', [BlogController::class, 'index'])
            ->name('blog-index');   // List all blogs
        Route::get('/{blog}', [BlogController::class, 'show'])
            ->name('blog-read');    // View single blog
    });

    // Authenticated routes — create, update, delete, restore
    Route::middleware('auth')->group(function () {
        Route::post('/', [BlogController::class, 'store'])
            ->name('blog-create');       // Create blog
        Route::put('/{blog}', [BlogController::class, 'update'])
            ->name('blog-update');       // Update blog
        Route::delete('/{blog}', [BlogController::class, 'destroy'])
            ->name('blog-softdelete');   // Soft delete blog
        Route::post('/restore/{blog}', [BlogController::class, 'restore'])
            ->name('blog-restore');      // Restore soft-deleted blog
        Route::delete('/hard/{blog}', [BlogController::class, 'hardDestroy'])
            ->name('blog-harddelete');      // Hard delete blog
    });
});