<?php

namespace App\Library\Infrastructure\BookRental\Database\Repositories;

use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\Repositories\BookRentalRepositoryInterface;
use App\Library\Domain\BookRental\ValueObjects\ReadingProgress;
use App\Library\Domain\BookRental\ValueObjects\Status;
use App\Library\Infrastructure\BookRental\Database\Models\BookRental;
use App\Library\Infrastructure\BookRental\Mappers\BookRentalMapper;

class EloquentBookRentalRepository implements BookRentalRepositoryInterface
{
    public function __construct(
        private readonly BookRentalMapper $bookRentalMapper
    ) {}

    /**
     * @param int $id The rental ID
     * @param int $userId The user ID
     * @return BookRentalEntity|null The rental entity or null if not found
     */
    public function findByIdAndUser(int $id, int $userId): ?BookRentalEntity
    {
        $bookRental = BookRental::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        return $bookRental ? $this->bookRentalMapper->fromModelToEntity($bookRental) : null;
    }

    /**
     * Find a rental by ID and user ID with associated book information.
     *
     * @param int $id The rental ID
     * @param int $userId The user ID
     * @return array{rental: BookRentalEntity, bookId: int}|null
     */
    public function findByIdAndUserWithBookId(int $id, int $userId): ?array
    {
        $bookRental = BookRental::with('book')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$bookRental) {
            return null;
        }

        return [
            'rental' => $this->bookRentalMapper->fromModelToEntity($bookRental),
            'bookId' => $bookRental->book_id,
        ];
    }

    /**
     * @param BookRentalEntity $bookRentalEntity The book rental entity to be saved.
     * @return BookRentalEntity The updated book rental entity after being saved.
     */
    public function save(BookRentalEntity $bookRentalEntity): BookRentalEntity
    {
        $bookRentalModel = $this->bookRentalMapper->fromEntityToModel($bookRentalEntity);
        $bookRentalModel->save();

        return $this->bookRentalMapper->fromModelToEntity($bookRentalModel->fresh());
    }

    /**
     * @param int $rentalId The unique identifier of the book rental.
     * @param ReadingProgress $progress The new reading progress value object.
     * @return BookRentalEntity The updated book rental entity.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the rental is not found.
     */
    public function updateReadingProgress(int $rentalId, ReadingProgress $progress): BookRentalEntity
    {
        $bookRentalModel = BookRental::findOrFail($rentalId);
        
        $bookRentalModel->reading_progress = $progress->getValue();
        $bookRentalModel->save();

        return $this->bookRentalMapper->fromModelToEntity($bookRentalModel->fresh());
    }

    /**
     * Marks a book rental as returned and updates the status to completed.
     *
     * @param int $rentalId The unique identifier of the book rental.
     * @return BookRentalEntity The updated book rental entity.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If the rental is not found.
     * @throws \DomainException If the rental is already returned/completed.
     */
    public function returnBook(int $rentalId): BookRentalEntity
    {
        $bookRentalModel = BookRental::findOrFail($rentalId);
        
        if ($bookRentalModel->status === Status::COMPLETED) {
            throw new \DomainException('This rental has already been returned');
        }
        
        $bookRentalModel->status = Status::COMPLETED;
        $bookRentalModel->returned_at = now();
        $bookRentalModel->reading_progress = 100;
        $bookRentalModel->save();

        return $this->bookRentalMapper->fromModelToEntity($bookRentalModel->fresh());
    }

    /**
     * Checks if a user currently has an active rental for a specific book.
     *
     * @param int $userId The unique identifier of the user.
     * @param int $bookId The unique identifier of the book.
     * @return bool True if an active rental exists for the given user and book, false otherwise.
     */
    public function hasActiveRentalForBook(int $userId, int $bookId): bool
    {
        return BookRental::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->where('status', 'active')
            ->whereNull('returned_at')
            ->exists();
    }
}