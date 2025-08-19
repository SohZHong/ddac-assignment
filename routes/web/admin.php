<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserManagementController;

Route::middleware(['auth', 'role:system_admin'])
    ->group(function () {
        Route::get('/admin', function () {
            return Inertia::render('Admin/Dashboard');
        })->name('admin.index');

        Route::get('/admin/users', [UserManagementController::class, 'index'])
            ->name('admin.users');
    });