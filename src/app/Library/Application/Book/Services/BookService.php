<?php

namespace App\Library\Application\Book\Services;

use App\Library\Application\Book\DTOs\SearchBookDTO;
use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class BookService
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository
    ) {}

    public function searchBooks(SearchBookDTO $dto): LengthAwarePaginator
    {
        $filters = array_filter([
            'title' => $dto->title,
            'author' => $dto->author,
            'genre' => $dto->genre,
            'available' => $dto->availableOnly,
        ]);

        $sort = [];
        if ($dto->sortBy) {
            $sort = [
                'field' => $dto->sortBy,
                'direction' => $dto->sortDirection ?? 'asc',
            ];
        }

        return $this->bookRepository->search($filters, $sort, $dto->perPage ?? 15);
    }

    public function getBookById(int $id): ?BookEntity
    {
        return $this->bookRepository->findById($id);
    }
}