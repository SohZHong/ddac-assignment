<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApprovalStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->needsApproval() && !$user->isApproved()) {
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
