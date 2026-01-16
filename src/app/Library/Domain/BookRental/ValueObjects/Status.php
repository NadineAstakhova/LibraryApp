<?php

namespace App\Library\Domain\BookRental\ValueObjects;

use DomainException;

class Status
{
    public const IN_RENTAL = 'in_rental';

    public const RETURNED = 'returned';

    public const ALL = [
        self::IN_RENTAL,
        self::RETURNED
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
}