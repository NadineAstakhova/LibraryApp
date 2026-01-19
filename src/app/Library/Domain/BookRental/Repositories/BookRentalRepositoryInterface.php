<?php

namespace App\Library\Domain\BookRental\Repositories;

use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\ValueObjects\ReadingProgress;

interface BookRentalRepositoryInterface
{

    /**
     * @param int $id The rental ID
     * @param int $userId The user ID
     * @return BookRentalEntity|null The rental entity or null if not found
     */
    public function findByIdAndUser(int $id, int $userId): ?BookRentalEntity;

    /**
     * @param BookRentalEntity $bookRentalEntity The entity to save
     * @return BookRentalEntity The saved entity with ID
     */
    public function save(BookRentalEntity $bookRentalEntity): BookRentalEntity;

    /**
     * @param int $rentalId The rental ID
     * @param ReadingProgress $progress The new reading progress
     * @return BookRentalEntity The updated entity
     */
    public function updateReadingProgress(int $rentalId, ReadingProgress $progress): BookRentalEntity;

    /**
     * @param int $rentalId The rental ID
     * @return BookRentalEntity The updated entity
     */
    public function returnBook(int $rentalId): BookRentalEntity;

    /**
     * Check if a user has an active rental for a specific book.
     *
     * @param int $userId The user ID
     * @param int $bookId The book ID
     * @return bool True if an active rental exists
     */
    public function hasActiveRentalForBook(int $userId, int $bookId): bool;

    /**
     * @param int $id The rental ID
     * @param int $userId The user ID
     * @return array{rental: BookRentalEntity, bookId: int}|null
     */
    public function findByIdAndUserWithBookId(int $id, int $userId): ?array;
}
