<?php

namespace App\Library\Domain\Book\ValueObjects;

/**
 * Encapsulates all filter, sort, and pagination options.
 */
readonly class BookSearchCriteria
{
    public const SORT_ASC = 'asc';
    public const SORT_DESC = 'desc';
    
    public const ALLOWED_SORT_FIELDS = [
        'title',
        'author',
        'genre',
        'publication_year',
        'available_copies',
        'created_at',
    ];

    public function __construct(
        private ?string $title = null,
        private ?string $author = null,
        private ?string $genre = null,
        private bool $availableOnly = false,
        private ?string $sortBy = 'title',
        private string $sortDirection = self::SORT_ASC,
        private int $perPage = 15,
        private int $page = 1,
    ) {
        $this->validateSortField($sortBy);
        $this->validateSortDirection($sortDirection);
        $this->validatePerPage($perPage);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function isAvailableOnly(): bool
    {
        return $this->availableOnly;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function hasTitleFilter(): bool
    {
        return $this->title !== null && $this->title !== '';
    }

    public function hasAuthorFilter(): bool
    {
        return $this->author !== null && $this->author !== '';
    }

    public function hasGenreFilter(): bool
    {
        return $this->genre !== null && $this->genre !== '';
    }

    private function validateSortField(?string $sortBy): void
    {
        if ($sortBy !== null && !in_array($sortBy, self::ALLOWED_SORT_FIELDS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid sort field "%s". Allowed fields: %s',
                    $sortBy,
                    implode(', ', self::ALLOWED_SORT_FIELDS)
                )
            );
        }
    }

    private function validateSortDirection(string $direction): void
    {
        if (!in_array($direction, [self::SORT_ASC, self::SORT_DESC], true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid sort direction "%s". Use "asc" or "desc".', $direction)
            );
        }
    }

    private function validatePerPage(int $perPage): void
    {
        if ($perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException(
                'Per page must be between 1 and 100.'
            );
        }
    }
}
