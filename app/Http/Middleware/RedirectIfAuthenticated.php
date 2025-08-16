<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        foreach ($guards as $guard) {
            if (auth()->guard($guard)->check()) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Already authenticated'], 200);
                }
                return redirect('/dashboard');
            }
        }

        return $next($request);
    }
}