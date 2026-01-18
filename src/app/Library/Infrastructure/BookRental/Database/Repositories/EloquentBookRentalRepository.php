<?php

namespace App\Library\Infrastructure\BookRental\Database\Repositories;

use App\Library\Application\BookRental\DTOs\BookRentalWithBookDTO;
use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\Repositories\BookRentalRepositoryInterface;
use App\Library\Infrastructure\Book\Mappers\BookMapper;
use App\Library\Infrastructure\BookRental\Database\Models\BookRental;
use App\Library\Infrastructure\BookRental\Mappers\BookRentalMapper;
use Illuminate\Database\Eloquent\Collection;

class EloquentBookRentalRepository implements BookRentalRepositoryInterface
{
    public function __construct(
        private readonly BookRentalMapper $bookRentalMapper,
        private readonly BookMapper $bookMapper
    ) {}

    /**
     * Retrieves a book rental record by its unique identifier and maps it to a DTO including the associated book information.
     *
     * @param int $id The unique identifier of the book rental record.
     *
     * @return BookRentalWithBookDTO|null The corresponding DTO with book information, or null if no record is found.
     */
    public function findById(int $id): ?BookRentalWithBookDTO
    {
        $bookRental = BookRental::with(['book', 'user'])->find($id);

        return $bookRental ? $this->bookRentalMapper->toDTOWithBook(
            $this->bookRentalMapper->fromModelToEntity($bookRental),
            $this->bookMapper->fromEloquentModelToEntity($bookRental->book)
        ) : null;
    }

    /**
     * Finds a book rental record by its ID and associated user ID, returning the data as a DTO
     * that includes details of the rental and the related book.
     *
     * @param int $id The ID of the book rental record.
     * @param int $userId The ID of the user associated with the book rental record.
     *
     * @return BookRentalWithBookDTO|null Returns a data transfer object containing the book rental and book details,
     *                                    or null if no matching record is found.
     */
    public function findByIdAndUser(int $id, int $userId): ?BookRentalWithBookDTO
    {
        $bookRental = BookRental::with(['book', 'user'])->where('id', $id)->where('user_id', $userId)->first();

        return $bookRental ? $this->bookRentalMapper->toDTOWithBook(
            $this->bookRentalMapper->fromModelToEntity($bookRental),
            $this->bookMapper->fromEloquentModelToEntity($bookRental->book)
        ) : null;
    }

    public function getUserActiveRentals(int $userId): Collection
    {
        // TODO: Implement getUserActiveRentals() method.
    }

    public function getUserRentalHistory(int $userId, int $perPage = 15)
    {
        // TODO: Implement getUserRentalHistory() method.
    }

    /**
     * Persists a book rental entity to the database and returns the updated entity.
     *
     * @param BookRentalEntity $bookRentalEntity The book rental entity to be saved.
     *
     * @return BookRentalEntity The updated book rental entity after being saved.
     */
    public function save(BookRentalEntity $bookRentalEntity): BookRentalEntity
    {
        $bookRentalModel = $this->bookRentalMapper->fromEntityToModel($bookRentalEntity);;
        $bookRentalModel->save();

        return $this->bookRentalMapper->fromModelToEntity($bookRentalModel->fresh());
    }

    /**
     * Checks if a user currently has an active rental for a specific book.
     *
     * @param int $userId The unique identifier of the user.
     * @param int $bookId The unique identifier of the book.
     *
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