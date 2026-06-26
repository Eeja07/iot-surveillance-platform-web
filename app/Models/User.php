<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail; // Import ini diaktifkan
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements MustVerifyEmail // Implementasi diaktifkan
{
  use HasApiTokens, HasRoles, HasFactory, Notifiable;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
    'fcm_token',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  /**
   * Mendefinisikan relasi one-to-many ke model Camera.
   * Satu User bisa memiliki banyak Camera.
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function cameras(): HasMany
  {
    return $this->hasMany(Camera::class);
  }
}
