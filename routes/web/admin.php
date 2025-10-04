<?php

use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\ContentController;
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
    Route::post('/admin/users', [UserManagementController::class, 'store'])
        ->name('admin.users.store');
    Route::put('/admin/users/{user}', [UserManagementController::class, 'update'])
        ->name('admin.users.update');
    Route::patch('/admin/users/{user}/role', [UserManagementController::class, 'updateRole'])
        ->name('admin.users.update-role');
    Route::patch('/admin/users/{user}/verify', [UserManagementController::class, 'verify'])
        ->name('admin.users.verify');
    Route::patch('/admin/users/{user}/unverify', [UserManagementController::class, 'unverify'])
        ->name('admin.users.unverify');
    Route::patch('/admin/users/{user}/verify-email', [UserManagementController::class, 'verifyEmail'])
        ->name('admin.users.verify-email');
    Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy'])
        ->name('admin.users.destroy');
    
    // Content management
    Route::get('/admin/content', [ContentController::class, 'index'])
        ->name('admin.content');
        
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