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

    /**
     * Extends the rental period by the specified number of days.
     *
     * @param int $days The number of days to extend the rental period. Defaults to the predefined value of DEFAULT_RENTAL_DAYS.
     *
     * @return self Returns a new instance of the rental with the updated extension details.
     * @throws \DomainException If the rental cannot be extended due to exceeding limits or other restrictions.
     */
    public function extend(int $days = self::DEFAULT_RENTAL_DAYS): self
    {
        if (!$this->canExtend()) {
            throw new \DomainException(
                sprintf(
                    'Cannot extend rental. Status: %s, Extensions: %d/%d, Overdue: %s',
                    $this->status->getValue(),
                    $this->extensionCount,
                    self::MAX_EXTENSIONS,
                    $this->rentalPeriod->isOverdue() ? 'Yes' : 'No'
                )
            );
        }

        return new self(
            id: $this->id,
            userId: $this->userId,
            bookId: $this->bookId,
            rentalPeriod: $this->rentalPeriod->extend($days),
            status: $this->status,
            readingProgress: $this->readingProgress,
            extensionCount: $this->extensionCount + 1,
        );
    }
}