<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin', // <--- important
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin'          => 'boolean', // <--- so you get true/false
    ];

    // Helper to use in Blade or controllers
    public function isAdmin()
    {
        return $this->is_admin === true;
    }

    // Fixes the BadMethodCallException for orders()
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
