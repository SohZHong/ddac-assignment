<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ProfessionalCredential;
use App\Notifications\ApprovalStatusChanged;
use App\Models\AdminLog;
use App\UserRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\UserRole as RoleEnum;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\AdminApprovalQueue;
use App\Services\AdminEventsPublisher;
use App\Services\CloudWatchMetrics;

class ApprovalController extends Controller
{
    /**
     * Display a listing of pending approvals.
     */
    public function index(): Response
    {
        $pendingUsers = User::with('professionalCredentials')
            ->where('approval_status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $pendingApprovalsCount = User::where('approval_status', 'pending')->count();
        $disk = config('filesystems.default');

        return Inertia::render('Admin/Approvals/Index', [
            'pendingUsers' => $pendingUsers->through(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'requested_role' => $user->requested_role,
                'created_at' => $user->created_at->format('M j, Y'),
                'credentials' => $user->professionalCredentials->map(fn ($credential) => [
                    'id' => $credential->id,
                    'type' => $credential->credential_type,
                    'number' => $credential->credential_number,
                    'issuer' => $credential->issuing_authority,
                    'issued_at' => $credential->issue_date->format('M j, Y'),
                    'expires_at' => $credential->expiry_date?->format('M j, Y'),
                    'document_url' => $credential->document_path ? Storage::disk($disk)->url($credential->document_path) : null,
                    'is_expired' => $credential->isExpired(),
                    'is_expiring_soon' => $credential->isExpiringSoon(),
                ]),
            ]),
            'pendingApprovalsCount' => $pendingApprovalsCount,
        ]);
    }

    /**
     * Show a specific pending approval.
     */
    public function show(User $user): Response
    {
        if (! Auth::user() || ! Auth::user()->isSystemAdmin()) {
            abort(403, 'You do not have permission to review this approval.');
        }

        $user->load('professionalCredentials');

        $requestedRoleLabel = null;
        if ($user->requested_role) {
            try {
                $requestedRoleLabel = RoleEnum::from($user->requested_role)->label();
            } catch (\Throwable $e) {
                $requestedRoleLabel = $user->requested_role;
            }
        }
        $disk = config('filesystems.default');

        return Inertia::render('Admin/Approvals/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'requested_role' => $user->requested_role,
                'requested_role_label' => $requestedRoleLabel,
                'created_at' => $user->created_at->format('M j, Y'),
                'credentials' => $user->professionalCredentials->map(fn ($credential) => [
                    'id' => $credential->id,
                    'type' => $credential->credential_type,
                    'number' => $credential->credential_number,
                    'issuer' => $credential->issuing_authority,
                    'issued_at' => $credential->issue_date->format('M j, Y'),
                    'expires_at' => $credential->expiry_date?->format('M j, Y'),
                    'document_url' => $credential->document_path ? Storage::disk($disk)->url($credential->document_path) : null,
                    'is_expired' => $credential->isExpired(),
                    'is_expiring_soon' => $credential->isExpiringSoon(),
                    'additional_info' => $credential->additional_info,
                ]),
            ],
        ]);
    }

    /**
     * Approve a user.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        $t0 = microtime(true);
        if (! Auth::user() || ! Auth::user()->isSystemAdmin()) {
            abort(403, 'You do not have permission to review this approval.');
        }

        if (!$user->isPending()) {
            return back()->withErrors(['approval' => 'This user is not pending approval.']);
        }

        // Apply the requested role on approval
        if ($user->requested_role) {
            $user->update([
                'role' => RoleEnum::from($user->requested_role),
                'requested_role' => null,
            ]);
        }

        $user->approve();

        AdminLog::create([
            'user_id' => Auth::id(),
            'action' => 'approval.approved',
            'target_type' => User::class,
            'target_id' => $user->id,
            'metadata' => [
                'approved_role' => $user->role->value,
                'target_name' => $user->name,
            ],
            'ip_address' => request()->ip(),
        ]);
        
        // Notify the user
        $user->notify(new ApprovalStatusChanged('approved'));

        // Publish async side-effects to SQS (non-blocking)
        try {
            app(AdminApprovalQueue::class)->publish([
                'type' => 'approval.approved',
                'userId' => $user->id,
                'actorId' => (int) Auth::id(),
                'email' => $user->email,
                'at' => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            // Do not block user flow if queue publish fails
        }

        // Publish SNS admin event (fan-out to email/SQS/etc.)
        try {
            $subject = 'Your application has been approved';
            $body = "Hi {$user->name},\n\nGreat news! Your professional account application has been approved.\nYou can now access your dashboard: " . url('/dashboard') . "\n\nThanks,\nThe Team";
            app(AdminEventsPublisher::class)->publish('approval.approved', [
                'userId' => $user->id,
                'actorId' => (int) Auth::id(),
                'email' => $user->email,
                'name' => $user->name,
            ], $subject, $body);
        } catch (\Throwable $e) {
            // non-blocking
        }

        // Emit latency metric
        $latencyMs = (microtime(true) - $t0) * 1000.0;
        app(CloudWatchMetrics::class)->putTiming('approvalLatencyMs', $latencyMs, [
            'route' => 'approve',
            'result' => 'approved',
        ]);

        return back()->with('success', 'User has been approved successfully.');
    }

    /**
     * Reject a user.
     */
    public function reject(Request $request, User $user): RedirectResponse
    {
        $t0 = microtime(true);
        if (! Auth::user() || ! Auth::user()->isSystemAdmin()) {
            abort(403, 'You do not have permission to review this approval.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if (!$user->isPending()) {
            return back()->withErrors(['approval' => 'This user is not pending approval.']);
        }

        // On rejection, clear requested role
        $user->update([
            'requested_role' => null,
        ]);
        $user->reject($request->reason);

        AdminLog::create([
            'user_id' => Auth::id(),
            'action' => 'approval.rejected',
            'target_type' => User::class,
            'target_id' => $user->id,
            'metadata' => [
                'reason' => $request->reason,
                'target_name' => $user->name,
            ],
            'ip_address' => request()->ip(),
        ]);
        
        // Notify the user
        $user->notify(new ApprovalStatusChanged('rejected', $request->reason));

        // Publish async side-effects to SQS (non-blocking)
        try {
            app(AdminApprovalQueue::class)->publish([
                'type' => 'approval.rejected',
                'userId' => $user->id,
                'actorId' => (int) Auth::id(),
                'email' => $user->email,
                'reason' => $request->reason,
                'at' => now()->toISOString(),
            ]);
        } catch (\Throwable $e) {
            // Do not block user flow if queue publish fails
        }

        // Publish SNS admin event (fan-out to email/SQS/etc.)
        try {
            $subject = 'Update on your application';
            $body = "Hi {$user->name},\n\nWe reviewed your application but could not approve it at this time.\nReason: {$request->reason}\n\nYou may update your profile and reapply here: " . url('/settings/profile') . "\n\nThanks,\nThe Team";
            app(AdminEventsPublisher::class)->publish('approval.rejected', [
                'userId' => $user->id,
                'actorId' => (int) Auth::id(),
                'email' => $user->email,
                'name' => $user->name,
                'reason' => $request->reason,
            ], $subject, $body);
        } catch (\Throwable $e) {
            // non-blocking
        }

        // Emit latency metric
        $latencyMs = (microtime(true) - $t0) * 1000.0;
        app(CloudWatchMetrics::class)->putTiming('approvalLatencyMs', $latencyMs, [
            'route' => 'reject',
            'result' => 'rejected',
        ]);

        return back()->with('success', 'User has been rejected.');
    }
}