<?php

namespace App\Library\Domain\User\Entities;

use App\Library\Domain\User\ValueObjects\Role;
use DateTimeImmutable;

class User
{
    private ?int $id;
    private string $name;
    private string $email;
    private string $passwordHash;
    private Role $role;
    private ?DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        ?int $id,
        string $name,
        string $email,
        string $passwordHash = '',
        Role|string $role = Role::USER,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->role = $role instanceof Role ? $role : Role::fromString($role);
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    // Factory-style helpers could be in a separate Factory class.
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        if ($this->id !== null) {
            return;
        }
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getRoleValue(): string
    {
        return $this->role->value;
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Check if the user has regular user role.
     */
    public function isUser(): bool
    {
        return $this->role->isUser();
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $dt): void
    {
        $this->createdAt = $dt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $dt): void
    {
        $this->updatedAt = $dt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'password_hash' => $this->passwordHash,
            'role' => $this->role->value,
            'created_at' => $this->createdAt?->format(DATE_ATOM),
            'updated_at' => $this->updatedAt?->format(DATE_ATOM),
        ];
    }
}