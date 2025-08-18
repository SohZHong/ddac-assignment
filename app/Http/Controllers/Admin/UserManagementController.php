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

class UserManagementController extends Controller
{
    /**
     * Display a listing of users with their roles.
     */
    public function index(): Response
    {
        $users = User::select(['id', 'name', 'email', 'role', 'requested_role', 'approval_status', 'email_verified_at', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $users->through(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'role_label' => $user->role->label(),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at->format('M j, Y'),
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
        ]);
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

        $user->role = $newRole;
        $user->save();

        // Log role change
        AdminLog::create([
            'user_id' => $currentUser->id,
            'action' => 'user.role_changed',
            'target_type' => User::class,
            'target_id' => $user->id,
            'metadata' => ['new_role' => $newRole->value],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "User role updated to {$newRole->label()} successfully.");
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
            'metadata' => ['email' => $user->email],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', 'User deleted successfully.');
    }
}
