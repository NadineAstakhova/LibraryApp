<?php

namespace App\Library\Infrastructure\Book\Database\Repositories;

use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Infrastructure\Book\Database\Models\Book;
use App\Library\Infrastructure\Book\Mappers\BookMapper;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentBookRepository implements BookRepositoryInterface
{

    public function __construct(
        private readonly BookMapper $mapper
    ) {}

    public function findById(int $id): ?BookEntity
    {
        $bookModel = Book::find($id); //todo maybe Cache?
        return $bookModel ? $this->mapper->fromEloquentModelToEntity($bookModel) : null;
    }

    public function search(array $filters, array $sort = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Book::query();

        //todo don't like this array
        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (!empty($filters['author'])) {
            $query->where('author', 'like', '%' . $filters['author'] . '%');
        }

        if (!empty($filters['genre'])) {
            $query->where('genre', $filters['genre']);
        }

        if (isset($filters['available']) && $filters['available']) {
            $query->where('available_copies', '>', 0);
        }

        if (!empty($sort['field'])) {
            $direction = $sort['direction'] ?? 'asc';
            $query->orderBy($sort['field'], $direction);
        } else {
            $query->orderBy('title', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function create(BookEntity $entity): BookEntity
    {
        // TODO: Implement create() method.
    }

    public function update(BookEntity $entity): BookEntity
    {
        // TODO: Implement update() method.
    }

    public function delete(int $id): bool
    {
        // TODO: Implement delete() method.
    }

    public function decrementAvailability(int $bookId): void
    {
        // TODO: Implement decrementAvailability() method.
    }

    public function incrementAvailability(int $bookId): void
    {
        // TODO: Implement incrementAvailability() method.
    }
}