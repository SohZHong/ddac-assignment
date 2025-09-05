<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\User;

class EventAttendancePolicy
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
        // Only managers/Admins can check in people for their own event
        if ($user->isSystemAdmin() || ($user->isHealthCampaignManager() && $event->created_by === $user->id)) {
            return true;
        }
        // Regular users cannot check themselves in
        return false;
    }

    public function delete(User $user, EventAttendance|string $attendance, Event $event, ?int $forUserId = null): bool
    {
        // Managers/Admins can undo check-in for anyone for their own event
        if ($user->isSystemAdmin() || ($user->isHealthCampaignManager() && $event->created_by === $user->id)) {
            return true;
        }
        if ($attendance instanceof EventAttendance) {
            return $attendance->user_id === $user->id;
        }
        if ($forUserId !== null) {
            return $forUserId === $user->id;
        }
        return true;
    }
}
