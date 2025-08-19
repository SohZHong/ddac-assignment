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

// System Admin Only Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'role:system_admin'])->group(function () {
    Route::get('/admin', function () {
        $pendingApprovalsCount = User::where('approval_status', 'pending')->count();
        return Inertia::render('Admin/Dashboard', [
            'pendingApprovalsCount' => $pendingApprovalsCount,
        ]);
    })->name('admin.index');
    
    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users');
        
    // Approval routes
    Route::get('/admin/approvals', [ApprovalController::class, 'index'])
        ->name('admin.approvals.index');
    Route::get('/admin/approvals/{user}', [ApprovalController::class, 'show'])
        ->name('admin.approvals.show');
    Route::post('/admin/approvals/{user}/approve', [ApprovalController::class, 'approve'])
        ->name('admin.approvals.approve');
    Route::post('/admin/approvals/{user}/reject', [ApprovalController::class, 'reject'])
        ->name('admin.approvals.reject');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/web/blog.php';
require __DIR__.'/web/schedule.php';
require __DIR__.'/web/healthcare.php';
require __DIR__.'/web/campaign.php';
require __DIR__.'/web/admin.php';
