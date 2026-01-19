<?php

namespace App\Library\Application\BookRental\DTOs;

readonly class UpdateReadingProgressDTO
{
    public function __construct(
        public int $userId,
        public int $progress,
    ) {
        if ($progress < 0 || $progress > 100) {
            throw new \InvalidArgumentException('Progress must be between 0 and 100');
        }
    }
}
