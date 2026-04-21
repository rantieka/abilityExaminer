<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuestionPolicy
{
  use HandlesAuthorization;

  public function viewAny(User $user): bool
  {
    return $user->hasRole(['super_admin', 'hr', 'spv']);
  }

  public function view(User $user, Question $question): bool
  {
    return $user->hasRole(['super_admin', 'hr', 'spv']);
  }

  public function create(User $user): bool
  {
    return $user->hasRole(['super_admin', 'hr', 'spv']);
  }

  public function update(User $user, Question $question): bool
  {
    return $user->hasRole(['super_admin', 'hr', 'spv']);
  }

  public function delete(User $user, Question $question): bool
  {
    return $user->hasRole(['super_admin', 'hr', 'spv']);
  }
}
