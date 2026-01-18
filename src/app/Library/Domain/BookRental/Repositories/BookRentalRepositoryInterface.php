<?php

namespace App\Library\Domain\BookRental\Repositories;

use App\Library\Application\BookRental\DTOs\BookRentalWithBookDTO;
use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use Illuminate\Database\Eloquent\Collection;

interface BookRentalRepositoryInterface
{
    public function findById(int $id): ?BookRentalWithBookDTO;
    public function findByIdAndUserEntity(int $id, int $userId): ?BookRentalEntity;
    public function findByIdAndUserWithBookInfo(int $id, int $userId): ?BookRentalWithBookDTO;
    public function getUserActiveRentals(int $userId): Collection;
    public function getUserRentalHistory(int $userId, int $perPage = 15);
    public function save(BookRentalEntity $bookRentalEntity): BookRentalEntity;
    public function hasActiveRentalForBook(int $userId, int $bookId): bool;
}