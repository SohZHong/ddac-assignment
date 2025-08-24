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
                    'document_url' => $credential->document_path ? Storage::disk('public')->url($credential->document_path) : null,
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
                    'document_url' => $credential->document_path ? Storage::disk('public')->url($credential->document_path) : null,
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

        return back()->with('success', 'User has been approved successfully.');
    }

    /**
     * Reject a user.
     */
    public function reject(Request $request, User $user): RedirectResponse
    {
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

        return back()->with('success', 'User has been rejected.');
    }
}