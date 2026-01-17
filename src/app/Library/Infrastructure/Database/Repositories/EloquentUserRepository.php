<?php

namespace App\Library\Infrastructure\Database\Repositories;

use App\Library\Domain\User\Entities\User as UserEntity;
use App\Library\Domain\User\Repositories\UserRepositoryInterface;
use App\Library\Infrastructure\Database\Mappers\UserMapper;
use App\Library\Infrastructure\Database\Models\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserMapper $mapper
    ) {}

    public function create(UserEntity $userEntity): UserEntity
    {
        $user = User::create([
            'name' => $userEntity->getName(),
            'email' => $userEntity->getEmail(),
            'password' => $userEntity->getPasswordHash(),
        ]);

        return $this->mapper->toEntity($user);
    }

    public function findByEmail(string $email): ?UserEntity
    {
        $model = User::where('email', $email)->first();

        return $model ? $this->mapper->toEntity($model) : null;
    }

    public function findById(int $id): ?UserEntity
    {
        // TODO: Implement findById() method.
    }
}