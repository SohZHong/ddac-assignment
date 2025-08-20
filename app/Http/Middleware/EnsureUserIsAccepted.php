<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAccepted
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

        // Public users (role 1) don't need acceptance
        if ($user->role->value === 1) {
            return $next($request);
        }

        // Healthcare professionals and campaign managers need admin verification
        if (!$user->is_verified) {
            return redirect()->route('pending-approval');
        }

        return $next($request);
    }
}
