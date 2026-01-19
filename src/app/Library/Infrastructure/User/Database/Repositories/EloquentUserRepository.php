<?php

namespace App\Library\Infrastructure\User\Database\Repositories;

use App\Library\Domain\User\Entities\User as UserEntity;
use App\Library\Domain\User\Repositories\UserRepositoryInterface;
use App\Library\Infrastructure\User\Mappers\UserMapper;
use App\Library\Infrastructure\User\Database\Models\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly UserMapper $mapper
    ) {}

    /**
     * @param \App\Library\Domain\User\Entities\User $userEntity
     *
     * @return \App\Library\Domain\User\Entities\User
     */
    public function create(UserEntity $userEntity): UserEntity
    {
        $user = User::create([
            'name' => $userEntity->getName(),
            'email' => $userEntity->getEmail(),
            'password' => $userEntity->getPasswordHash(),
        ]);

        return $this->mapper->toEntity($user);
    }

    /**
     * @param string $email
     *
     * @return \App\Library\Domain\User\Entities\User|null
     */
    public function findByEmail(string $email): ?UserEntity
    {
        $model = User::where('email', $email)->first();

        return $model ? $this->mapper->toEntity($model) : null;
    }

    /**
     * @param int $id
     *
     * @return \App\Library\Domain\User\Entities\User|null
     */
    public function findById(int $id): ?UserEntity
    {
        $model = User::find($id);

        return $model ? $this->mapper->toEntity($model) : null;
    }

    /**
     * @param int $userId User ID
     * @param string $hashedPassword The new hashed password
     * @return bool True if updated successfully
     */
    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $affectedRows = User::where('id', $userId)
            ->update(['password' => $hashedPassword]);

        return $affectedRows > 0;
    }

    /**
     * @param int $userId User ID
     * @param string $name The new name
     * @return UserEntity|null The updated user entity or null if not found
     */
    public function updateProfile(int $userId, string $name): ?UserEntity
    {
        $user = User::find($userId);

        if (!$user) {
            return null;
        }

        $user->name = $name;
        $user->save();

        return $this->mapper->toEntity($user);
    }
}