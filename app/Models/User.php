<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserRole;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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
}
