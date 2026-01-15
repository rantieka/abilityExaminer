<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
  /**
   * HasRoles: Trait from Spatie Permission required by Filament Shield
   * to handle this user's roles & permissions.
   */
  use HasFactory, Notifiable, HasRoles;

  public function canImpersonate()
  {
    // only super_admin can impersonate
    return $this->hasRole('super_admin'); 
  }
  public function canBeImpersonated()
  {
    // only super_admin can be impersonated
    return !$this->hasRole('super_admin');
  }

  /**
   * The attributes that are mass assignable.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
    'email_verified_at', // Added so it can be updated via Filament Form
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }
}
