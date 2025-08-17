<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        $request->fulfill();

        Log::info('Email verified', [
            'user_id' => $request->user()->id,
            'email' => $request->user()->email,
            'verified_at' => $request->user()->email_verified_at,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
