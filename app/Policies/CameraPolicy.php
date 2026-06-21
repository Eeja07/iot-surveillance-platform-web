<?php

namespace App\Policies;

use App\Models\Camera;
use App\Models\User;

class CameraPolicy
{
  public function view(User $user, Camera $camera): bool
  {
    return $user->id == $camera->user_id;
  }

  public function viewAny(User $user): bool
  {
    return true; // atau cek role/permission tertentu
  }

  public function create(User $user): bool
  {
    return $user !== null; // atau cek role
  }

  public function update(User $user, Camera $camera): bool
  {
    return $user->id == $camera->user_id;
  }

  public function delete(User $user, Camera $camera): bool
  {
    return $user->id == $camera->user_id;
  }
}
