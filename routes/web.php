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

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/web/blog.php';
require __DIR__.'/web/schedule.php';
require __DIR__.'/web/healthcare.php';
require __DIR__.'/web/campaign.php';
require __DIR__.'/web/admin.php';
