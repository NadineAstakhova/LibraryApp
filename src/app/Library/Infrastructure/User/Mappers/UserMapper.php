<?php

namespace App\Library\Infrastructure\User\Mappers;

use App\Library\Application\User\DTOs\RegisterUserDTO;
use App\Library\Infrastructure\User\Database\Models\User as EloquentUser;
use App\Library\Domain\User\Entities\User as UserEntity;
use Illuminate\Support\Facades\Hash;

class UserMapper
{
    public function toEntity(EloquentUser $eloquentModel): UserEntity
    {
        return new UserEntity(
            $eloquentModel->id,
            $eloquentModel->name,
            $eloquentModel->email,
            $eloquentModel->password
        );
    }

    public function fromRegisterUserDTO(RegisterUserDTO $dto): UserEntity
    {
        return new UserEntity(
            id: null,
            name: $dto->name,
            email: $dto->email,
            passwordHash: Hash::make($dto->password)
        );
    }

    public function toArray(UserEntity $entity): array
    {
        return $entity->toArray();
    }

    public function toModel(UserEntity $entity): EloquentUser
    {
        return new EloquentUser(['id' => $entity->getId(), 'name' => $entity->getName(), 'email' => $entity->getEmail(), 'password' => $entity->getPasswordHash()]);
    }

}