<?php

use App\Http\Controllers\Admin\ApprovalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserManagementController;
use App\Models\User;

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