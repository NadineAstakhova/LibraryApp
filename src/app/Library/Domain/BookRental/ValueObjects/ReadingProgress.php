<?php

namespace App\Library\Domain\BookRental\ValueObjects;

class ReadingProgress
{
    public function __construct(
        private int $value
    ) {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException("Progress must be between 0 and 100");
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function isComplete(): bool
    {
        return $this->value === 100;
    }

    public function increment(int $amount): self
    {
        return new self(min(100, $this->value + $amount));
    }
}