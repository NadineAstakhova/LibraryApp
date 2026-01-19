<?php

namespace App\Library\Application\Exceptions;

use Exception;

class BookNotAvailableForRentException extends Exception
{
    public function __construct(string $message = 'Book is not available for rent')
    {
        parent::__construct($message, 409);
    }
}
