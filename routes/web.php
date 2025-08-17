<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\PendingApprovalController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Pending approval route (requires auth but not verification by admin)
Route::get('/pending-approval', [PendingApprovalController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('pending-approval');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'verified_by_admin'])->name('dashboard');

// Healthcare Professional Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'verified', 'verified_by_admin', 'role:2,3,4'])->group(function () {
    Route::get('/healthcare', function () {
        return Inertia::render('Healthcare/Dashboard');
    })->name('healthcare.index');
});

// Health Campaign Manager Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'verified', 'verified_by_admin', 'role:3,4'])->group(function () {
    Route::get('/campaigns', function () {
        return Inertia::render('Campaigns/Dashboard');
    })->name('campaigns.index');
});

// System Admin Only Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'verified', 'verified_by_admin', 'role:4'])->group(function () {
    Route::get('/admin', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.index');
    
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users');
    
    Route::patch('/admin/users/{user}/role', [UserManagementController::class, 'updateRole'])
        ->name('admin.users.update-role');
    
    Route::patch('/admin/users/{user}/verify', [UserManagementController::class, 'verify'])
        ->name('admin.users.verify');
    
    Route::patch('/admin/users/{user}/unverify', [UserManagementController::class, 'unverify'])
        ->name('admin.users.unverify');
    
    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])
        ->name('admin.users.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
