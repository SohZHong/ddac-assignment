<?php

namespace App\Policies;

use App\Models\User;
use App\UserRole;

class UserPolicy
{
    /**
     * Determine if the user can review approval requests.
     */
    public function reviewApproval(User $user, User $target): bool
    {
        // Only system admins can review approvals
        if (!$user->isSystemAdmin()) {
            return false;
        }

        // Can't review own approval
        if ($user->id === $target->id) {
            return false;
        }

        // Can only review pending approvals
        return $target->isPending();
    }

    /**
     * Determine if the user can view professional credentials.
     */
    public function viewCredentials(User $user, User $target): bool
    {
        // System admins can view all credentials
        if ($user->isSystemAdmin()) {
            return true;
        }

        // Users can view their own credentials
        return $user->id === $target->id;
    }
}
