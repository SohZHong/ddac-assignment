<?php

namespace App;

enum UserRole: string
{
    case PUBLIC_USER = '1';
    case HEALTHCARE_PROFESSIONAL = '2';
    case HEALTH_CAMPAIGN_MANAGER = '3';
    case SYSTEM_ADMIN = '4';

    /**
     * Get the human-readable label for the role
     */
    public function label(): string
    {
        return match($this) {
            self::PUBLIC_USER => 'Public User',
            self::HEALTHCARE_PROFESSIONAL => 'Healthcare Professional',
            self::HEALTH_CAMPAIGN_MANAGER => 'Health Campaign Manager',
            self::SYSTEM_ADMIN => 'System Administrator',
        };
    }

    /**
     * Get roles that can be assigned by the current role
     */
    public function canAssignRoles(): array
    {
        return match($this) {
            self::SYSTEM_ADMIN => [
                self::PUBLIC_USER,
                self::HEALTHCARE_PROFESSIONAL,
                self::HEALTH_CAMPAIGN_MANAGER,
                self::SYSTEM_ADMIN,
            ],
            self::HEALTH_CAMPAIGN_MANAGER => [
                self::PUBLIC_USER,
                self::HEALTHCARE_PROFESSIONAL,
            ],
            default => [],
        };
    }

    /**
     * Check if this role can manage other roles
     */
    public function canManageRoles(): bool
    {
        return in_array($this, [self::SYSTEM_ADMIN, self::HEALTH_CAMPAIGN_MANAGER]);
    }

    /**
     * Get all available roles
     */
    public static function all(): array
    {
        return [
            self::PUBLIC_USER,
            self::HEALTHCARE_PROFESSIONAL,
            self::HEALTH_CAMPAIGN_MANAGER,
            self::SYSTEM_ADMIN,
        ];
    }
}
