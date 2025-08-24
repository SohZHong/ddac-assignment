<?php

namespace App\Policies;

use App\UserRole;
use App\Models\Blog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BlogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Blog $blog): bool
    {
        // Only professionals and campaign masters can create
        return $user->role === UserRole::HEALTHCARE_PROFESSIONAL || $user->role === UserRole::HEALTH_CAMPAIGN_MANAGER;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Only the blog owner can update.
     */
    public function update(User $user, Blog $blog): bool
    {
        return $user->id === $blog->author_id;
    }

    /**
     * Owner or system_admin can soft delete
     */
    public function delete(User $user, Blog $blog): bool
    {
        return $user->id === $blog->author_id || $user->role === UserRole::SYSTEM_ADMIN;
    }

    /**
     * Only System Admin can restore.
     */
    public function restore(User $user, Blog $blog): bool
    {
        return $user->role === UserRole::SYSTEM_ADMIN;
    }

    /**
     * Only System Admin can force delete
     */
    public function forceDelete(User $user, Blog $blog): bool
    {
        return $user->role === UserRole::SYSTEM_ADMIN;
    }
}
