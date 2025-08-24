<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  The roles that are allowed to access this route
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $userRole = $user->role;

        // Convert provided role identifiers (e.g., 'system_admin' or '4') to UserRole enums
        $allowedRoles = [];
        foreach ($roles as $roleIdentifier) {
            $enum = $this->resolveRoleToEnum($roleIdentifier);
            if ($enum !== null) {
                $allowedRoles[] = $enum;
            }
        }

        // Check if the user's role is in the allowed roles
        if (!in_array($userRole, $allowedRoles)) {
            // Return 403 Forbidden for API routes or redirect for web routes
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Access denied. Insufficient permissions.'], 403);
            }
            
            abort(403, 'Access denied. You do not have permission to access this resource.');
        }

        // Check approval status for professional roles
        if ($user->needsApproval() && !$user->isApproved()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your professional account is pending approval.',
                ], 403);
            }

            return redirect()->route('approval.pending');
        }

        return $next($request);
    }

    /**
     * Resolve a role identifier (slug or backing value) to a UserRole enum instance.
     */
    private function resolveRoleToEnum(string $roleIdentifier): ?UserRole
    {
        // Try direct backing value first (e.g., '1', '2', '3', '4')
        $enum = UserRole::tryFrom((string) $roleIdentifier);
        if ($enum instanceof UserRole) {
            return $enum;
        }

        // Map legacy slug strings to enum
        $slug = strtolower(trim($roleIdentifier));
        return match ($slug) {
            'public_user' => UserRole::PUBLIC_USER,
            'healthcare_professional' => UserRole::HEALTHCARE_PROFESSIONAL,
            'health_campaign_manager' => UserRole::HEALTH_CAMPAIGN_MANAGER,
            'system_admin' => UserRole::SYSTEM_ADMIN,
            default => null,
        };
    }
}