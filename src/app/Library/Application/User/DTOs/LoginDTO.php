<?php

namespace App\Library\Application\User\DTOs;

readonly class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}
}