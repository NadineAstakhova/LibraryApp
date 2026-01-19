<?php

namespace App\Library\Application\BookRental\DTOs;

readonly class ReturnBookDTO
{
    public function __construct(
        public int $userId,
    ) {}
}
