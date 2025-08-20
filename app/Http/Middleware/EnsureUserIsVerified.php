<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // If user is not logged in, let other middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Public users and System Admins don't need verification
        if (!$user->requiresVerification() || $user->isSystemAdmin()) {
            return $next($request);
        }

        // Healthcare professionals and campaign managers need verification
        if (!$user->is_verified) {
            return redirect()->route('pending-approval');
        }

        return $next($request);
    }
}
