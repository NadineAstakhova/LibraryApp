<?php

namespace App\Library\Domain\User\Repositories;

use App\Library\Domain\User\Entities\User as DomainUser;

interface UserRepositoryInterface
{
    /**
     * Persist the domain user and return the persisted domain user (with id set).
     */
    public function create(DomainUser $user): DomainUser;

    /**
     * Find a user by email (returns DomainUser or null).
     */
    public function findByEmail(string $email): ?DomainUser;

    /**
     * Find a user by id (returns DomainUser or null).
     */
    public function findById(int $id): ?DomainUser;

}