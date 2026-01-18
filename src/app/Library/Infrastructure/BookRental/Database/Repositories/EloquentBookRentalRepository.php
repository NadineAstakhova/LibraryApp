<?php

namespace App\Library\Infrastructure\BookRental\Database\Repositories;

use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\Repositories\BookRentalRepositoryInterface;
use App\Library\Infrastructure\BookRental\Database\Models\BookRental;
use App\Library\Infrastructure\BookRental\Mappers\BookRentalMapper;
use Illuminate\Database\Eloquent\Collection;

class EloquentBookRentalRepository implements BookRentalRepositoryInterface
{
    public function __construct(
        private readonly BookRentalMapper $bookRentalMapper
    ) {}

    public function findById(int $id): ?BookRentalEntity
    {
        // TODO: Implement findById() method.
    }

    public function getUserActiveRentals(int $userId): Collection
    {
        // TODO: Implement getUserActiveRentals() method.
    }

    public function getUserRentalHistory(int $userId, int $perPage = 15)
    {
        // TODO: Implement getUserRentalHistory() method.
    }

    public function save(BookRentalEntity $bookRentalEntity): BookRentalEntity
    {
        $bookRentalModel = $this->bookRentalMapper->fromEntityToModel($bookRentalEntity);;
        $bookRentalModel->save();

        return $this->bookRentalMapper->fromModelToEntity($bookRentalModel->fresh());
    }

    public function hasActiveRentalForBook(int $userId, int $bookId): bool
    {
        return BookRental::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->where('status', 'active')
            ->whereNull('returned_at')
            ->exists();
    }
}