<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\CustomVerifyEmail;
use App\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'license_number',
        'medical_specialty',
        'institution_name',
        'years_experience',
        'registration_body',
        'organization_name',
        'job_title',
        'organization_type',
        'focus_areas',
        'work_email',
        'is_verified',
        'verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Check if the user has a specific role
     */
    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if the user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Check if the user is a public user
     */
    public function isPublicUser(): bool
    {
        return $this->hasRole(UserRole::PUBLIC_USER);
    }

    /**
     * Check if the user is a healthcare professional
     */
    public function isHealthcareProfessional(): bool
    {
        return $this->hasRole(UserRole::HEALTHCARE_PROFESSIONAL);
    }

    /**
     * Check if the user is a health campaign manager
     */
    public function isHealthCampaignManager(): bool
    {
        return $this->hasRole(UserRole::HEALTH_CAMPAIGN_MANAGER);
    }

    /**
     * Check if the user is a system admin
     */
    public function isSystemAdmin(): bool
    {
        return $this->hasRole(UserRole::SYSTEM_ADMIN);
    }

    /**
     * Check if the user can manage roles
     */
    public function canManageRoles(): bool
    {
        return $this->role->canManageRoles();
    }

    /**
     * Get roles that this user can assign to others
     */
    public function getAssignableRoles(): array
    {
        return $this->role->canAssignRoles();
    }

    /**
     * Check if the user requires admin verification
     */
    public function requiresVerification(): bool
    {
        return $this->isHealthcareProfessional() || $this->isHealthCampaignManager();
    }

    /**
     * Check if the user is verified by admin (for healthcare professionals and campaign managers)
     */
    public function isVerifiedByAdmin(): bool
    {
        return $this->is_verified === true;
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }
}
