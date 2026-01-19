<?php

namespace App\Library\Application\Exceptions;

use Exception;

class ActiveRentalExistsException extends Exception
{
    public function __construct(string $message = 'An active rental already exists for this book')
    {
        parent::__construct($message, 409);
    }
}