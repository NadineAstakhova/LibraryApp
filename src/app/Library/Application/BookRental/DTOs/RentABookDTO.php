<?php

namespace App\Library\Application\BookRental\DTOs;

readonly class RentABookDTO
{
    public function __construct(
        public int $userId,
        public int $bookId,
        public int $rentalDays = 14,
    ) {
        if ($rentalDays < 1 || $rentalDays > 90) {
            throw new \InvalidArgumentException('Rental days must be between 1 and 90');
        }
    }
}