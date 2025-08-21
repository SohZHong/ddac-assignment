<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserRole;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'requested_role',
        'approval_status',
        'rejection_reason',
        'approved_at',
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
            'requested_role' => 'string',
            'approved_at' => 'datetime',
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
     * Get the user's professional credentials.
     */
    public function professionalCredentials(): HasMany
    {
        return $this->hasMany(ProfessionalCredential::class);
    }

    /**
     * Check if the user needs approval
     */
    public function needsApproval(): bool
    {
        return in_array($this->role, [
            UserRole::HEALTHCARE_PROFESSIONAL,
            UserRole::HEALTH_CAMPAIGN_MANAGER,
        ]);
    }

    /**
     * Check if the user is approved
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Check if the user is pending approval
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Check if the user is rejected
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Approve the user
     */
    public function approve(): void
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the user
     */
    public function reject(string $reason): void
    {
        $this->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_at' => null,
        ]);
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
     * Get their blogs
     */
    public function blogs()
    {
        return $this->hasMany(Blog::class, 'author_id');
    }

    /**
     * Get their schedules
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'healthcare_id');
    }

    /**
     * Get their bookings
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'patient_id');
    }

    /**
     * If this user is a healthcare professional, get their bookings
     */
    public function healthcareBookings()
    {
        return $this->hasManyThrough(
            Booking::class,
            Schedule::class,
            'healthcare_id', // Foreign key on schedules table
            'schedule_id',   // Foreign key on bookings table
            'id',            // Local key on users table
            'id'             // Local key on schedules table
        );
    }

    /**
     *  Get all quizzes created by a healthcare professional
     */
    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'healthcare_id');
    }

    /**
     * Get all relevant reports
     */

    public function consultationReports()
    {
        return $this->hasMany(ConsultationReport::class, 'user_id');
    }

    // If this user is a doctor
    public function uploadedConsultationReports()
    {
        return $this->hasMany(ConsultationReport::class, 'uploaded_by');
    }
}
