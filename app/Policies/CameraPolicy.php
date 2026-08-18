<?php

namespace App\Policies;

use App\Models\Camera;
use App\Models\User;

class CameraPolicy
{
  public function before(User $user, string $ability): ?bool
  {
    if ($user->hasRole('admin')) {
      return true;
    }
    return null;
  }

  public function view(User $user, Camera $camera): bool
  {
    return $user->hasRole('admin') || $user->id === $camera->user_id;
  }

  public function viewAny(User $user): bool
  {
    return true;
  }

  public function create(User $user): bool
  {
    return $user !== null;
  }

  public function update(User $user, Camera $camera): bool
  {
    return $user->hasRole('admin') || $user->id === $camera->user_id;
  }

  public function delete(User $user, Camera $camera): bool
  {
    return $user->hasRole('admin') || $user->id === $camera->user_id;
  }
}

