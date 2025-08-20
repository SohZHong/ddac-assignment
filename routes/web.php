<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Models\User;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/approval-pending', function () {
    return Inertia::render('auth/PendingApproval');
})->name('approval.pending');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/web/blog.php';
require __DIR__.'/web/booking.php';
require __DIR__.'/web/schedule.php';
require __DIR__.'/web/healthcare.php';
require __DIR__.'/web/campaign.php';
require __DIR__.'/web/admin.php';
