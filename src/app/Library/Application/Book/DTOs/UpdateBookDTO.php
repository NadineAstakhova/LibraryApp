<?php

namespace App\Library\Application\Book\DTOs;

readonly class UpdateBookDTO
{
    public function __construct(
        public int $id,
        public int $version,
        public ?string $title = null,
        public ?string $author = null,
        public ?string $isbn = null,
        public ?string $genre = null,
        public ?string $description = null,
        public ?int $totalCopies = null,
        public ?int $publicationYear = null,
    ) {}
}
