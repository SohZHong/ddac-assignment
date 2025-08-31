<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\SystemActivityController;
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

Route::get('/livekit-test', function () {
    return Inertia::render('LivekitTest');
})->middleware(['auth', 'verified'])->name('livekit.test');

// Healthcare Professional Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'role:healthcare_professional,health_campaign_manager,system_admin'])->group(function () {
    Route::get('/healthcare', function () {
        return Inertia::render('Healthcare/Dashboard');
    })->name('healthcare.index');
});

// Health Campaign Manager Routes - UI (GET routes with Inertia::render)
Route::middleware(['auth', 'role:health_campaign_manager,system_admin'])->group(function () {
    Route::get('/campaigns', function () {
        $campaigns = \App\Models\Campaign::with('creator')
            ->when(auth()->user()->isHealthCampaignManager(), function ($query) {
                return $query->where('created_by', auth()->id());
            })
            ->latest()
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'description' => $campaign->description,
                    'type' => $campaign->type,
                    'status' => $campaign->status,
                    'start_date' => $campaign->start_date->format('Y-m-d'),
                    'end_date' => $campaign->end_date->format('Y-m-d'),
                    'target_audience' => $campaign->target_audience,
                    'target_reach' => $campaign->target_reach,
                    'budget' => $campaign->budget,
                    'location' => $campaign->location,
                    'creator' => $campaign->creator->name,
                    'created_at' => $campaign->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $campaign->updated_at->format('Y-m-d H:i:s'),
                    'is_running' => $campaign->isRunning(),
                    'progress_percentage' => $campaign->getProgressPercentage(),
                    'duration_days' => $campaign->getDurationInDays(),
                ];
            });

        return Inertia::render('Campaigns/Dashboard', [
            'campaigns' => $campaigns,
        ]);
    })->name('campaigns.index');

    Route::get('/campaigns/list', function () {
        $campaigns = \App\Models\Campaign::with('creator')
            ->when(auth()->user()->isHealthCampaignManager(), function ($query) {
                return $query->where('created_by', auth()->id());
            })
            ->latest()
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'description' => $campaign->description,
                    'type' => $campaign->type,
                    'status' => $campaign->status,
                    'start_date' => $campaign->start_date->format('Y-m-d'),
                    'end_date' => $campaign->end_date->format('Y-m-d'),
                    'target_audience' => $campaign->target_audience,
                    'target_reach' => $campaign->target_reach,
                    'budget' => $campaign->budget,
                    'location' => $campaign->location,
                    'creator' => $campaign->creator->name,
                    'created_at' => $campaign->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $campaign->updated_at->format('Y-m-d H:i:s'),
                    'is_running' => $campaign->isRunning(),
                    'progress_percentage' => $campaign->getProgressPercentage(),
                    'duration_days' => $campaign->getDurationInDays(),
                ];
            });

        return Inertia::render('Campaigns/Index', [
            'campaigns' => $campaigns,
        ]);
    })->name('campaigns.list');
});

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

    // System Activity
    Route::get('/admin/system-activity', [SystemActivityController::class, 'index'])
        ->name('admin.system.index');
    Route::post('/admin/system-activity/incidents/{incident}/resolve', [SystemActivityController::class, 'resolveIncident'])
        ->name('admin.system.resolve-incident');
    Route::post('/admin/system-activity/incidents', [SystemActivityController::class, 'storeIncident'])
        ->name('admin.system.store-incident');
    Route::post('/admin/system-activity/incidents/{incident}/reopen', [SystemActivityController::class, 'reopenIncident'])
        ->name('admin.system.reopen-incident');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/web/blog.php';
require __DIR__.'/web/booking.php';
require __DIR__.'/web/schedule.php';
require __DIR__.'/web/healthcare.php';
require __DIR__.'/web/campaign.php';
require __DIR__.'/web/event.php';
require __DIR__.'/web/admin.php';