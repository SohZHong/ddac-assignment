<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class QuizPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Only healthcare professionals can view.
     */
    public function healthcareView(User $user, Quiz $quiz): bool
    {
        return $quiz->healthcare_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Only healthcare professionals can update.
     */
    public function update(User $user, Quiz $quiz): bool
    {
        return $quiz->healthcare_id === $user->id;
    }

    /**
     * Only healthcare professionals can delete.
     */
    public function delete(User $user, Quiz $quiz): bool
    {
        return $quiz->healthcare_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Quiz $quiz): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Quiz $quiz): bool
    {
        return false;
    }
}
