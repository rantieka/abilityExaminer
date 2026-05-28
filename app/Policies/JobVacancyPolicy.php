<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\JobVacancy;
use Illuminate\Auth\Access\HandlesAuthorization;

class JobVacancyPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:JobVacancy');
    }

    public function view(AuthUser $authUser, JobVacancy $jobVacancy): bool
    {
        return $authUser->can('View:JobVacancy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:JobVacancy');
    }

    public function update(AuthUser $authUser, JobVacancy $jobVacancy): bool
    {
        return $authUser->can('Update:JobVacancy');
    }

    public function delete(AuthUser $authUser, JobVacancy $jobVacancy): bool
    {
        return $authUser->can('Delete:JobVacancy');
    }

    public function restore(AuthUser $authUser, JobVacancy $jobVacancy): bool
    {
        return $authUser->can('Restore:JobVacancy');
    }

    public function forceDelete(AuthUser $authUser, JobVacancy $jobVacancy): bool
    {
        return $authUser->can('ForceDelete:JobVacancy');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:JobVacancy');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:JobVacancy');
    }

    public function replicate(AuthUser $authUser, JobVacancy $jobVacancy): bool
    {
        return $authUser->can('Replicate:JobVacancy');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:JobVacancy');
    }

}