<?php

namespace App\Library\Domain\Book\ValueObjects;

/**
 * An ISBN (International Standard Book Number) is a unique identifier for books.
 * It helps bookstores, libraries, distributors, and readers find and reference your book correctly.
 * Each format of your book (paperback vs hardcover) needs its own ISBN.
 */
class ISBN
{
    public function __construct(
        private string $value
    ) {
        if (!$this->isValid($value)) {
            throw new \InvalidArgumentException("Invalid ISBN: {$value}");
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function isValid(string $isbn): bool
    {
        // Remove hyphens and spaces
        $isbn = preg_replace('/[\s-]+/', '', $isbn);

        // Check if it's ISBN-10 or ISBN-13
        return preg_match('/^(?=(?:\D*\d){10}(?:(?:\D*\d){3})?$)[\d-]+$/', $isbn);
    }
}