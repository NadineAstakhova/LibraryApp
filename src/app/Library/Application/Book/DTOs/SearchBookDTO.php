<?php

namespace App\Library\Application\Book\DTOs;

readonly class SearchBookDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $author = null,
        public ?string $genre = null,
        public ?bool $availableOnly = null,
        public ?string $sortBy = null,
        public ?string $sortDirection = 'asc',
        public ?int $perPage = 15,
    ) {}
}