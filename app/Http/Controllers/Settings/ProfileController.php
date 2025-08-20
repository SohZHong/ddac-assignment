<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();  
        $changes = [];
        $originalData = $user->only(['name', 'email']);
        $request->user()->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
            $changes['email'] = [
                'old' => $originalData['email'],
                'new' => $user->email
            ];
        }

        if ($user->isDirty('name')) {
            $changes['name'] = [
                'old' => $originalData['name'],
                'new' => $user->name
            ];
        }

        $user->save();

        if (!empty($changes)) {
            Log::info('User profile updated', [
                'user_id' => $user->id,
                'changes' => $changes
            ]);
        }

        return to_route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        $user->tokens()->delete(); 
        $user->currentAccessToken()?->delete(); 
        $user->sessions()->delete();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
