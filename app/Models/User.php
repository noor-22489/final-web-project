<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// Import the JWT contract
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // We added 'role' here
        'is_active',
        'qualification',
        'department',
        'roll_number',
        'program',
        'batch',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // REQUIRED METHOD 1: Get the identifier
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // REQUIRED METHOD 2: Add custom claims (like role) to the token
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }
}