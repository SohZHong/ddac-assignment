<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;

class EventRegistrationPolicy
{
    /**
     * List registrations for a given event.
     * Event creators (campaign managers) and system admins can view.
     */
    public function viewAny(User $user, Event $event): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }
        // Event creator can view
        return $user->isHealthCampaignManager() && $event->created_by === $user->id;
    }

    /**
     * Create a registration for the given event.
     * Any authenticated user can register when registrations are enabled.
     */
    public function create(User $user, Event $event): bool
    {
        return $user->hasVerifiedEmail();
    }

    /**
     * Delete a registration. Only the owner can delete their own registration.
     */
    public function delete(User $user, EventRegistration|string $registration, Event $event): bool
    {
        if ($registration instanceof EventRegistration) {
            return $registration->user_id === $user->id;
        }
        // If no registration found, allow noop (controller handles existence)
        return true;
    }
}
