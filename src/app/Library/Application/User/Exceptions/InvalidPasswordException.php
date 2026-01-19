<?php

namespace App\Library\Application\User\Exceptions;

use Exception;

class InvalidPasswordException extends Exception
{
    public function __construct()
    {
        parent::__construct('The current password is incorrect.', 401);
    }
}
