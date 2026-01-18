<?php

namespace App\Library\Domain\BookRental\ValueObjects;

use Carbon\Carbon;

class RentalPeriod
{
    public function __construct(
        private Carbon $rentedAt,
        private Carbon $dueDate,
        private ?Carbon $returnedAt = null,
    ) {
        if ($dueDate->lessThan($rentedAt)) {
            throw new \InvalidArgumentException('Due date cannot be before rented date');
        }
    }

    public function getRentedAt(): Carbon
    {
        return $this->rentedAt;
    }

    public function getDueDate(): Carbon
    {
        return $this->dueDate;
    }

    public function getReturnedAt(): ?Carbon
    {
        return $this->returnedAt;
    }

    public function isOverdue(): bool
    {
        return $this->returnedAt === null && $this->dueDate->isPast();
    }

    public function daysRemaining(): int
    {
        if ($this->returnedAt !== null) {
            return 0;
        }

        return max(0, now()->diffInDays($this->dueDate));
    }

    public static function createNew(int $days = 14): self
    {
        return new self(
            rentedAt: now(),
            dueDate: now()->addDays($days),
        );
    }

    public function extend(int $days): self
    {
        return new self(
            rentedAt: $this->rentedAt,
            dueDate: $this->dueDate->copy()->addDays($days),
            returnedAt: $this->returnedAt,
        );
    }
}