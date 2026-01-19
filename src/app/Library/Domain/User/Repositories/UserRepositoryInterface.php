<?php

namespace App\Library\Domain\User\Repositories;

use App\Library\Domain\User\Entities\User as UserEntity;

interface UserRepositoryInterface
{
    /**
     * @param UserEntity $userEntity The user entity to be created
     *
     * @return UserEntity The created user entity
     */
    public function create(UserEntity $userEntity): UserEntity;

    /**
     * Find a user by their email address.
     *
     * @param string $email The email address to search for
     *
     * @return UserEntity|null The user entity if found, or null if not found
     */
    public function findByEmail(string $email): ?UserEntity;

    /**
     * Find a user entity by its ID.
     *
     * @param int $id The ID of the user to find
     *
     * @return UserEntity|null The found user entity or null if not found
     */
    public function findById(int $id): ?UserEntity;

    /**
     * @param int $userId User ID
     * @param string $hashedPassword The new hashed password
     * @return bool True if updated successfully
     */
    public function updatePassword(int $userId, string $hashedPassword): bool;

    /**
     * @param int $userId User ID
     * @param string $name The new name
     * @return UserEntity|null The updated user entity or null if not found
     */
    public function updateProfile(int $userId, string $name): ?UserEntity;
}