<?php

namespace App\Library\Domain\BookRental\Entities;

use App\Library\Domain\BookRental\ValueObjects\ReadingProgress;
use App\Library\Domain\BookRental\ValueObjects\RentalPeriod;
use App\Library\Domain\BookRental\ValueObjects\Status;

class BookRental
{
    private const MAX_EXTENSIONS = 5;
    private const DEFAULT_RENTAL_DAYS = 14;

    public function __construct(
        private ?int $id,
        private int $userId,
        private int $bookId,
        private RentalPeriod $rentalPeriod,
        private Status $status,
        private ReadingProgress $readingProgress,
        private int $extensionCount,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookId(): int
    {
        return $this->bookId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getRentalPeriod(): RentalPeriod
    {
        return $this->rentalPeriod;
    }


    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getReadingProgress(): ReadingProgress
    {
        return $this->readingProgress;
    }

    public function getExtensionCount(): int
    {
        return $this->extensionCount;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setBookId(int $bookId): void
    {
        $this->bookId = $bookId;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public function setReadingProgress(ReadingProgress $readingProgress): void
    {
        $this->readingProgress = $readingProgress;
    }

    public function setExtensionCount(int $extensionCount): void
    {
        $this->extensionCount = $extensionCount;
    }

    public function canExtend(): bool
    {
        return $this->status->isActive()
            && $this->extensionCount < self::MAX_EXTENSIONS
            && !$this->rentalPeriod->isOverdue();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'book_id' => $this->bookId,
            'rented_at' => $this->rentalPeriod->getRentedAt(),
            'due_date' => $this->rentalPeriod->getDueDate(),
            'returned_at' => $this->rentalPeriod->getReturnedAt(),
            'status' => $this->status->getValue(),
            'reading_progress' => $this->readingProgress->getValue(),
            'extension_count' => $this->extensionCount,
        ];
    }
}