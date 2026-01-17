<?php

namespace App\Library\Domain\Book\Repositories;

use App\Library\Domain\Book\Entities\Book as BookEntity;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookRepositoryInterface
{
    public function findById(int $id): ?BookEntity;
    public function search(array $filters, array $sort = [], int $perPage = 15): LengthAwarePaginator;
    public function create(BookEntity $entity): BookEntity;
    public function update(BookEntity $entity): BookEntity;
    public function delete(int $id): bool;
    public function decrementAvailability(int $bookId): void;
    public function incrementAvailability(int $bookId): void;
}