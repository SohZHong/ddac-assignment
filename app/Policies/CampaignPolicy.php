<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use App\UserRole;

class CampaignPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isHealthCampaignManager() || $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        // Campaign managers can view their own campaigns
        if ($user->isHealthCampaignManager()) {
            return $campaign->created_by === $user->id;
        }

        // System admins can view all campaigns
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isHealthCampaignManager() || $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        // Campaign managers can update their own campaigns
        if ($user->isHealthCampaignManager()) {
            return $campaign->created_by === $user->id;
        }

        // System admins can update all campaigns
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        // Campaign managers can delete their own campaigns
        if ($user->isHealthCampaignManager()) {
            return $campaign->created_by === $user->id;
        }

        // System admins can delete all campaigns
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Campaign $campaign): bool
    {
        // Only system admins can restore campaigns
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Campaign $campaign): bool
    {
        // Only system admins can permanently delete campaigns
        return $user->isSystemAdmin();
    }
}
