<?php

namespace App\Library\Application\Book\DTOs;

readonly class SearchBookDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $author = null,
        public ?string $genre = null,
        public bool $availableOnly = false,
        public ?string $sortBy = 'title',
        public string $sortDirection = 'asc',
        public int $perPage = 15,
        public int $page = 1,
    ) {}
}