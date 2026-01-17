<?php

namespace App\Library\Domain\User\Repositories;

use App\Library\Domain\User\Entities\User as UserEntity;

interface UserRepositoryInterface
{
    /**
     * Persist the domain user and return the persisted UserEntity (with id set).
     */
    public function create(UserEntity $userEntity): UserEntity;

    /**
     * Find a user by email (returns UserEntity or null).
     */
    public function findByEmail(string $email): ?UserEntity;

    /**
     * Find a user by id (returns UserEntity or null).
     */
    public function findById(int $id): ?UserEntity;

}