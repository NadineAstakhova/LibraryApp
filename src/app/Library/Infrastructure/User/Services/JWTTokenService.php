<?php

namespace App\Library\Infrastructure\User\Services;

use App\Library\Application\User\Services\TokenServiceInterface;
use App\Library\Domain\User\Entities\User as UserEntity;
use App\Library\Domain\User\ValueObjects\Role;
use App\Library\Infrastructure\User\Database\Models\User as EloquentUser;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTTokenService implements TokenServiceInterface
{
    /**
     * @param UserEntity $user The domain user entity
     * @return string The generated JWT token
     */
    public function generateToken(UserEntity $user): string
    {
        $eloquentUser = new EloquentUser();
        $eloquentUser->id = $user->getId();
        $eloquentUser->name = $user->getName();
        $eloquentUser->email = $user->getEmail();
        $eloquentUser->role = $user->getRoleValue();
        $eloquentUser->exists = true;

        return JWTAuth::fromUser($eloquentUser);
    }

    /**
     * @return string The new JWT token
     */
    public function refreshToken(): string
    {
        return JWTAuth::refresh(JWTAuth::getToken());
    }

    /**
     * @return int TTL in minutes
     */
    public function getTTL(): int
    {
        return config('jwt.ttl', 60);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function logout(): void
    {
        JWTAuth::invalidate(JWTAuth::getToken());
    }

    /**
     * @return UserEntity|null The authenticated user or null
     */
    public function getAuthenticatedUser(): ?UserEntity
    {
        $eloquentUser = JWTAuth::parseToken()->authenticate();
        
        if (!$eloquentUser) {
            return null;
        }

        return new UserEntity(
            id: $eloquentUser->id,
            name: $eloquentUser->name,
            email: $eloquentUser->email,
            passwordHash: $eloquentUser->password,
            role: Role::fromString($eloquentUser->role),
            createdAt: $eloquentUser->created_at ? new \DateTimeImmutable($eloquentUser->created_at->toDateTimeString()) : null,
            updatedAt: $eloquentUser->updated_at ? new \DateTimeImmutable($eloquentUser->updated_at->toDateTimeString()) : null,
        );
    }
}
