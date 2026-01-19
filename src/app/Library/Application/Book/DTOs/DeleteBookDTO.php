<?php

namespace App\Library\Application\Book\DTOs;

readonly class DeleteBookDTO
{
    public function __construct(
        public int $id,
        public int $version,
    ) {}
}
