<?php

namespace App\Library\Application\User\DTOs;

readonly class RegisterUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}