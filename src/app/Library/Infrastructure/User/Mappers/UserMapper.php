<?php

namespace App\Library\Infrastructure\User\Mappers;

use App\Library\Application\User\DTOs\RegisterUserDTO;
use App\Library\Domain\User\ValueObjects\Role;
use App\Library\Infrastructure\User\Database\Models\User as EloquentUser;
use App\Library\Domain\User\Entities\User as UserEntity;
use DateTimeImmutable;
use Illuminate\Support\Facades\Hash;

class UserMapper
{
    /**
     * Convert an Eloquent User model to a domain User entity.
     *
     * @param EloquentUser $eloquentModel
     *
     * @return UserEntity
     * @throws \Exception
     */
    public function toEntity(EloquentUser $eloquentModel): UserEntity
    {
        return new UserEntity(
            id: $eloquentModel->id,
            name: $eloquentModel->name,
            email: $eloquentModel->email,
            passwordHash: $eloquentModel->password,
            role: Role::fromString($eloquentModel->role ?? 'user'),
            createdAt: $eloquentModel->created_at
                ? new DateTimeImmutable($eloquentModel->created_at->toDateTimeString())
                : null,
            updatedAt: $eloquentModel->updated_at
                ? new DateTimeImmutable($eloquentModel->updated_at->toDateTimeString())
                : null,
        );
    }

    /**
     * Create a domain User entity from a RegisterUserDTO.
     *
     * @param RegisterUserDTO $dto
     * @return UserEntity
     */
    public function fromRegisterUserDTO(RegisterUserDTO $dto): UserEntity
    {
        return new UserEntity(
            id: null,
            name: $dto->name,
            email: $dto->email,
            passwordHash: Hash::make($dto->password)
        );
    }

    /**
     * Convert a domain User entity to an array representation.
     * This is for internal use and includes all fields.
     *
     * @param UserEntity $entity
     * @return array
     */
    public function toArray(UserEntity $entity): array
    {
        return $this->toPublicArray($entity);
    }

    /**
     * Convert a domain User entity to a public-safe array representation.
     * Excludes sensitive data like password hash.
     *
     * @param UserEntity $entity
     * @return array
     */
    public function toPublicArray(UserEntity $entity): array
    {
        return [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'email' => $entity->getEmail(),
            'role' => $entity->getRoleValue(),
            'created_at' => $entity->getCreatedAt()?->format(DATE_ATOM),
            'updated_at' => $entity->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}