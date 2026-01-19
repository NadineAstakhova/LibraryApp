<?php

namespace App\Library\Application\Exceptions;

use Exception;

class RentalNotFoundException extends Exception
{
    public function __construct(string $message = 'Rental not found')
    {
        parent::__construct($message, 404);
    }
}