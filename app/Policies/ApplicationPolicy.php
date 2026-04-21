<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApplicationPolicy
{
  use HandlesAuthorization;

  /**
  * Determine whether the user can view any models.
  */
  public function viewAny(User $user): bool
  {
    return true; // Everyone can see the list
  }

  /**
  * Determine whether the user can view the model.
  */
  public function view(User $user, Application $application): bool
  {
    return true; // Everyone can view details
  }

  /**
  * Determine whether the user can create models.
  */
  public function create(User $user): bool
  {
    if ($user->hasRole('spv')) {
      return false;
    }
    return true;
  }

  /**
  * Determine whether the user can update the model.
  */
  public function update(User $user, Application $application): bool
  {
    if ($user->hasRole('spv')) {
      return false;
    }
    return true;
  }

  /**
  * Determine whether the user can delete the model.
  */
  public function delete(User $user, Application $application): bool
  {
    if ($user->hasRole('spv')) {
      return false;
    }
    return true;
  }

  /**
  * Determine whether the user can restore the model.
  */
  public function restore(User $user, Application $application): bool
  {
    if ($user->hasRole('spv')) {
      return false;
    }
    return true;
  }

  /**
  * Determine whether the user can permanently delete the model.
  */
  public function forceDelete(User $user, Application $application): bool
  {
    if ($user->hasRole('spv')) {
      return false;
    }
    return true;
  }
}
