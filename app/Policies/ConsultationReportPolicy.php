<?php

namespace App\Policies;

use App\Models\ConsultationReport;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConsultationReportPolicy
{

    /**
     * Determine if the given user can create/update the consultation report.
     */
    public function manage(User $user): bool
    {
        // Only healthcare professionals can manage reports
        return $user->isHealthcareProfessional();
    }

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
    public function view(User $user, ConsultationReport $consultationReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ConsultationReport $consultationReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ConsultationReport $consultationReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ConsultationReport $consultationReport): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ConsultationReport $consultationReport): bool
    {
        return false;
    }
}
