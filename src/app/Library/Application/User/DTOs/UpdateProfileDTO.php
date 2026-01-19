<?php

namespace App\Library\Application\User\DTOs;

readonly class UpdateProfileDTO
{
    public function __construct(
        public int $userId,
        public ?string $name = null,
    ) {}

    public function hasUpdates(): bool
    {
        return $this->name !== null;
    }
}
