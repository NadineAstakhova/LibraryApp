<?php

namespace App\Library\Application\Exceptions;

use Exception;

/**
 * Exception thrown when an optimistic locking conflict occurs.
 * This happens when trying to update/delete a resource that has been
 * modified by another process since it was last read.
 */
class OptimisticLockException extends Exception
{
    public function __construct(
        string $resourceType,
        int $resourceId,
        int $expectedVersion,
        ?int $actualVersion = null
    ) {
        $message = sprintf(
            '%s with ID %d has been modified by another process. Expected version %d%s.',
            $resourceType,
            $resourceId,
            $expectedVersion,
            $actualVersion !== null ? ", but found version $actualVersion" : ''
        );

        parent::__construct($message, 409);
    }
}