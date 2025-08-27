<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;

class EventRegistrationPolicy
{
    public function viewAny(User $user, Event $event): bool
    {
        if ($user->isSystemAdmin()) {
            return true;
        }
        return $user->isHealthCampaignManager() && $event->created_by === $user->id;
    }

    public function create(User $user, Event $event, ?int $forUserId = null): bool
    {
        // Managers/Admins can create for others on their own event
        if ($user->isSystemAdmin() || ($user->isHealthCampaignManager() && $event->created_by === $user->id)) {
            return true;
        }
        // Regular users can create for themselves only
        return $forUserId === null || $forUserId === $user->id;
    }

    public function update(User $user, Event $event, EventRegistration $registration): bool
    {
        // Managers/Admins can update registrations on their own event
        if ($user->isSystemAdmin() || ($user->isHealthCampaignManager() && $event->created_by === $user->id)) {
            return true;
        }
        return false;
    }

    public function delete(User $user, EventRegistration|string $registration, Event $event, ?int $forUserId = null): bool
    {
        // Managers/Admins can delete registrations on their own event
        if ($user->isSystemAdmin() || ($user->isHealthCampaignManager() && $event->created_by === $user->id)) {
            return true;
        }
        // Regular users can delete their own registration
        if ($registration instanceof EventRegistration) {
            return $registration->user_id === $user->id;
        }
        if ($forUserId !== null) {
            return $forUserId === $user->id;
        }
        return true;
    }
}
