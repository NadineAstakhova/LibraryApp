<?php

namespace App\Library\Infrastructure\Database\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
        'id', //todo change it. fast fix
        'name',
        'email',
        'password',
        'role',
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

    // JWTSubject methods
    public function getJWTIdentifier(): string
    {
        return (string)$this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    protected static function newFactory(): \Illuminate\Database\Eloquent\Factories\Factory|UserFactory
    {
        return UserFactory::new();
    }
}