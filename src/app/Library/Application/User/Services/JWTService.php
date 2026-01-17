<?php

namespace App\Library\Application\User\Services;

use App\Library\Infrastructure\User\Database\Models\User as EloquentUser;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTService
{
    public function generateToken(EloquentUser $user): string
    {
        return JWTAuth::fromUser($user);
    }

    public function getTTL(): int
    {
        return config('jwt.ttl', 60);
    }

    /**
     * @throws \Exception
     */
    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }
}