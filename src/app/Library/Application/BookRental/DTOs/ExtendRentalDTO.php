<?php

namespace App\Library\Application\BookRental\DTOs;

class ExtendRentalDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $extensionDays = 14,
    ) {
        if ($extensionDays < 1 || $extensionDays > 30) {
            throw new \InvalidArgumentException('Extension days must be between 1 and 30');
        }
    }
}