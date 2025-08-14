<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserManagementController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Healthcare Professional Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'role:healthcare_professional,health_campaign_manager,system_admin'])->group(function () {
    Route::get('/healthcare', function () {
        return Inertia::render('Healthcare/Dashboard');
    })->name('healthcare.index');
});

// Health Campaign Manager Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'role:health_campaign_manager,system_admin'])->group(function () {
    Route::get('/campaigns', function () {
        return Inertia::render('Campaigns/Dashboard');
    })->name('campaigns.index');
});

// System Admin Only Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'role:system_admin'])->group(function () {
    Route::get('/admin', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.index');
    
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/blog.php';
