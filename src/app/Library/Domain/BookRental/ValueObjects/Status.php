<?php

namespace App\Library\Domain\BookRental\ValueObjects;

use DomainException;

class Status
{
    public const ACTIVE = 'active';
    public const RETURNED = 'returned';
    public const OVERDUE = 'overdue';

    public const ALL = [
        self::ACTIVE,
        self::RETURNED,
        self::OVERDUE,
    ];

    private string $value;

    public function __construct(string $status)
    {
        if (! in_array($status, self::ALL)) {
            throw new DomainException('Invalid book rental status');
        }

        $this->value = $status;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::RETURNED;
    }

    public function isOverdue(): bool
    {
        return $this->value === self::OVERDUE;
    }
}