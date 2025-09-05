<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\AdminLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users with their roles.
     */
    public function index(): Response
    {
        $query = request('q');

        $users = User::select(['id', 'name', 'email', 'role', 'requested_role', 'approval_status', 'email_verified_at', 'created_at'])
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'ILIKE', "%{$query}%")
                      ->orWhere('email', 'ILIKE', "%{$query}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $users->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'work_email' => $user->work_email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'email_verified_at' => $user->email_verified_at,
                'is_verified' => $user->is_verified,
                'verified_at' => $user->verified_at?->format('M j, Y'),
                'created_at' => $user->created_at->format('M j, Y'),
                'needs_verification' => $user->requiresVerification(),
                // Professional details
                'license_number' => $user->license_number,
                'medical_specialty' => $user->medical_specialty,
                'institution_name' => $user->institution_name,
                'organization_name' => $user->organization_name,
                'job_title' => $user->job_title,
                'organization_type' => $user->organization_type,
                'focus_areas' => $user->focus_areas,
                'registration_body' => $user->registration_body,
            ];
        });

        $pendingApprovalsCount = User::where('approval_status', 'pending')->count();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'available_roles' => collect(UserRole::all())->map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'pendingApprovalsCount' => $pendingApprovalsCount,
            'q' => $query,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Only system admins can create users
        if (!$currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to create users.');
        }

        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => ['required', 'string', Rule::in(array_map(fn($role) => $role->value, UserRole::all()))],
            'work_email' => 'nullable|email|max:255',
            // Healthcare Professional fields
            'license_number' => 'nullable|string|max:255',
            'medical_specialty' => 'nullable|string|max:255',
            'institution_name' => 'nullable|string|max:255',
            'registration_body' => 'nullable|string|max:255',
            // Health Campaign Manager fields
            'organization_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'organization_type' => 'nullable|string|max:255',
            'focus_areas' => 'nullable|string|max:255',
        ]);

        $role = UserRole::from($request->role);

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(12)), // Generate random password
            'role' => $role,
            'work_email' => $request->work_email,
            'email_verified_at' => now(), // Auto-verify admin-created users
            'approval_status' => 'approved', // Auto-approve admin-created users
            'approved_at' => now(),
            // Healthcare Professional fields
            'license_number' => $request->license_number,
            'medical_specialty' => $request->medical_specialty,
            'institution_name' => $request->institution_name,
            'registration_body' => $request->registration_body,
            // Health Campaign Manager fields
            'organization_name' => $request->organization_name,
            'job_title' => $request->job_title,
            'organization_type' => $request->organization_type,
            'focus_areas' => $request->focus_areas,
        ]);

        // Log user creation
        AdminLog::create([
            'user_id' => $currentUser->id,
            'action' => 'user.created',
            'target_type' => User::class,
            'target_id' => $user->id,
            'metadata' => [
                'actor_id' => $currentUser->id,
                'actor_name' => $currentUser->name,
                'target_id' => $user->id,
                'target_name' => $user->name,
                'target_email' => $user->email,
                'role' => $role->value,
                'role_label' => $role->label(),
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "User {$user->name} created successfully with role {$role->label()}.");
    }

    /**
     * Update a user's role.
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Check if current user can manage roles
        if (!$currentUser->canManageRoles()) {
            abort(403, 'You do not have permission to manage user roles.');
        }

        // Validate the role
        $request->validate([
            'role' => ['required', 'string', Rule::in(array_map(fn($role) => $role->value, UserRole::all()))],
        ]);

        $newRole = UserRole::from($request->role);

        // Check if current user can assign this role
        if (!in_array($newRole, $currentUser->getAssignableRoles())) {
            abort(403, 'You do not have permission to assign this role.');
        }

        // Prevent users from changing their own role
        if ($user->id === $currentUser->id) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        // Capture old role before changing
        $oldRole = $user->role; // enum instance (or null)

        $user->role = $newRole;
        $user->save();

        // Log role change
        AdminLog::create([
            'user_id' => $currentUser->id,
            'action' => 'user.role_changed',
            'target_type' => User::class,
            'target_id' => $user->id,
            'metadata' => [
                'actor_id' => $currentUser->id,
                'actor_name' => $currentUser->name,
                'actor_email' => $currentUser->email,
                'target_id' => $user->id,
                'target_name' => $user->name,
                'target_email' => $user->email,
                'old_role' => $oldRole?->value,
                'old_role_label' => $oldRole?->label(),
                'new_role' => $newRole->value,
                'new_role_label' => $newRole->label(),
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "User role updated to {$newRole->label()} successfully.");
    }

    /**
     * Verify a user (approve their professional credentials).
     */
    public function verify(Request $request, User $user): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Only system admins can verify users
        if (!$currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to verify users.');
        }

        $request->validate([]);

        $user->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);

        return back()->with('success', "{$user->name} has been verified successfully.");
    }

    /**
     * Unverify a user (revoke their verification).
     */
    public function unverify(Request $request, User $user): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Only system admins can unverify users
        if (!$currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to unverify users.');
        }

        $request->validate([]);

        $user->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);

        return back()->with('success', "{$user->name}'s verification has been revoked.");
    }

    /**
     * Delete a user (system admin only).
     */
    public function destroy(User $user): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        // Only system admins can delete users
        if (!$currentUser->isSystemAdmin()) {
            abort(403, 'You do not have permission to delete users.');
        }

        // Prevent users from deleting themselves
        if ($user->id === $currentUser->id) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        $user->delete();

        // Log deletion
        AdminLog::create([
            'user_id' => $currentUser->id,
            'action' => 'user.deleted',
            'target_type' => User::class,
            'target_id' => $user->id,
            'metadata' => ['email' => $user->email, 'target_name' => $user->name],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'User deleted successfully.');
    }
}
