<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\UserRole;

class EventPolicy
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
    public function view(User $user, Event $event): bool
    {
        // All authenticated users can view published events
        if ($event->status === 'published') {
            return true;
        }

        // Campaign managers can view their own events
        if ($user->isHealthCampaignManager()) {
            return $event->created_by === $user->id;
        }

        // System admins can view all events
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can view published events (authenticated access).
     */
    public function viewPublished(User $user): bool
    {
        // All authenticated users can view published events
        return true;
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
    public function update(User $user, Event $event): bool
    {
        // Campaign managers can update their own events
        if ($user->isHealthCampaignManager()) {
            return $event->created_by === $user->id;
        }

        // System admins can update all events
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Event $event): bool
    {
        // Campaign managers can delete their own events
        if ($user->isHealthCampaignManager()) {
            return $event->created_by === $user->id;
        }

        // System admins can delete all events
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Event $event): bool
    {
        // Only system admins can restore events
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Event $event): bool
    {
        // Only system admins can permanently delete events
        return $user->isSystemAdmin();
    }
}
