<?php

namespace App\Library\Infrastructure\Database\Repositories;

use App\Library\Domain\User\Entities\User as DomainUser;
use App\Library\Domain\User\Repositories\UserRepositoryInterface;
use App\Library\Infrastructure\Database\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class EloquentUserRepository implements UserRepositoryInterface
{


    public function create(DomainUser $user): DomainUser
    {

    }

    public function findByEmail(string $email): ?DomainUser
    {
        // TODO: Implement findByEmail() method.
    }

    public function findById(int $id): ?DomainUser
    {
        // TODO: Implement findById() method.
    }
}