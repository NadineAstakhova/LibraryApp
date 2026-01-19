<?php

namespace App\Library\Application\Exceptions;

use Exception;

class BookHasActiveRentalsException extends Exception
{
    public function __construct(int $bookId, int $activeRentalsCount)
    {
        $message = sprintf(
            'Cannot delete book with ID %d because it has %d active rental(s).',
            $bookId,
            $activeRentalsCount
        );

        parent::__construct($message, 409);
    }
}