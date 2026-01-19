<?php

namespace App\Library\Application\User\DTOs;

readonly class UpdatePasswordDTO
{
    public function __construct(
        public int $userId,
        public string $currentPassword,
        public string $newPassword,
    ) {}
}
