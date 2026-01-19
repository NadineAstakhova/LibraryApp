<?php

namespace App\Library\Infrastructure\User\Database\Models;

use App\Library\Infrastructure\BookRental\Database\Models\BookRental;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'users';

    protected $fillable = [
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

    public function bookRentals(): HasMany
    {
        return $this->hasMany(BookRental::class);
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

    protected static function newFactory(): Factory|UserFactory
    {
        return UserFactory::new();
    }
}