<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Ver a candidatura, as respostas e baixar o currículo.
     */
    public function view(User $user, Application $application): bool
    {
        return $user->belongsToCompany($application->company_id);
    }

    /**
     * Mover entre etapas, reprovar e reativar.
     */
    public function update(User $user, Application $application): bool
    {
        return $user->belongsToCompany($application->company_id) && $user->canManageHiring();
    }
}
