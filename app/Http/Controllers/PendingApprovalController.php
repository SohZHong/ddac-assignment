<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PendingApprovalController extends Controller
{
    /**
     * Show the pending approval page.
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        return Inertia::render('auth/PendingApproval', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->label(),
                'submitted_at' => $user->created_at->format('M d, Y'),
            ]
        ]);
    }
}
