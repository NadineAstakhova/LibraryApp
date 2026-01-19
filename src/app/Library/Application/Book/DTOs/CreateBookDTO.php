<?php

namespace App\Library\Application\Book\DTOs;

readonly class CreateBookDTO
{
    public function __construct(
        public string $title,
        public string $author,
        public string $isbn,
        public string $genre,
        public ?string $description = null,
        public int $totalCopies = 1,
        public ?int $publicationYear = null,
    ) {}
}
