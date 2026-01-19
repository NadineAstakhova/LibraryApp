<?php

namespace App\Library\Application\User\Services;

use App\Library\Domain\User\Entities\User as UserEntity;

interface TokenServiceInterface
{
    /**
     * Generate a JWT token for the given user entity.
     *
     * @param UserEntity $user The domain user entity
     * @return string The generated JWT token
     */
    public function generateToken(UserEntity $user): string;

    /**
     * Refresh the current token and return a new one.
     *
     * @return string The new JWT token
     */
    public function refreshToken(): string;

    /**
     * Get the token time-to-live in minutes.
     *
     * @return int TTL in minutes
     */
    public function getTTL(): int;

    /**
     * Invalidate the current token.
     *
     * @return void
     */
    public function logout(): void;

    /**
     * Get the currently authenticated user entity.
     *
     * @return UserEntity|null The authenticated user or null
     */
    public function getAuthenticatedUser(): ?UserEntity;
}
