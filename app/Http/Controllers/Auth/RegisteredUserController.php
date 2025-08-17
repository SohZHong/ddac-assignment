<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => [
                'required', 
                'confirmed',                 
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'role' => [
                'required',
                'string',
                Rule::in(['1', '2', '3']), // Only allow specific roles during registration
            ],
            'work_email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
        ], [
            'role.required' => 'Please select a user role.',
            'role.in' => 'Invalid role selected.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?: '1',
            'license_number'=> $request->license_number,
            'medical_specialty'=> $request->medical_specialty,
            'institution_name'=> $request->institution_name,
            'years_experience'=> $request->years_experience,
            'registration_body'=> $request->registration_body,
            'organization_name'=> $request->organization_name,
            'job_title'=> $request->job_title,
            'organization_type'=> $request->organization_type,
            'focus_areas'=> $request->focus_areas,
            'work_email'=> $request->work_email,
            'is_verified' => $request->role === '1' ? true : false,
            'verified_at' => $request->role === '1' ? now() : null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
