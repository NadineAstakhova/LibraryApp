<?php

namespace App\Library\Application\BookRental\DTOs;

use Carbon\Carbon;

readonly class BookRentalWithBookDTO
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $bookId,
        public Carbon $rentedAt,
        public Carbon $dueDate,
        public ?Carbon $returnedAt,
        public string $status,
        public int $readingProgress,
        public int $extensionCount,
        public int $daysRemaining,
        public bool $canExtend,
        public bool $isOverdue,
        public array $book,
    ) {}
}