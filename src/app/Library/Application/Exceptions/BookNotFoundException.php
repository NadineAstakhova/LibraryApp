<?php

namespace App\Library\Application\Exceptions;

use Exception;

class BookNotFoundException extends Exception
{
    public function __construct(int|string $bookIdOrMessage)
    {
        $message = is_int($bookIdOrMessage) 
            ? "Book with ID {$bookIdOrMessage} not found"
            : $bookIdOrMessage;
            
        parent::__construct($message, 404);
    }
}