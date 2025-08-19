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

        // Convert string roles to UserRole enum instances
        $allowedRoles = array_map(function ($role) {
            return UserRole::from($role);
        }, $roles);

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
}