<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Job $job): bool
    {
        return $user->belongsToCompany($job->company_id);
    }

    public function create(User $user): bool
    {
        return $user->canManageHiring();
    }

    /**
     * Editar, publicar, pausar e gerenciar as etapas da vaga.
     */
    public function update(User $user, Job $job): bool
    {
        return $user->belongsToCompany($job->company_id) && $user->canManageHiring();
    }

    public function delete(User $user, Job $job): bool
    {
        return $user->belongsToCompany($job->company_id) && $user->isAdmin();
    }
}
