<?php

namespace App\Library\Application\BookRental\Services;

use App\Library\Application\BookRental\DTOs\ExtendRentalDTO;
use App\Library\Application\BookRental\DTOs\RentABookDTO;
use App\Library\Application\BookRental\DTOs\ReturnBookDTO;
use App\Library\Application\BookRental\DTOs\UpdateReadingProgressDTO;
use App\Library\Application\Exceptions\ActiveRentalExistsException;
use App\Library\Application\Exceptions\BookNotAvailableForRentException;
use App\Library\Application\Exceptions\BookNotFoundException;
use App\Library\Application\Exceptions\OptimisticLockException;
use App\Library\Application\Exceptions\RentalNotFoundException;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\Repositories\BookRentalRepositoryInterface;
use App\Library\Domain\BookRental\ValueObjects\ReadingProgress;
use App\Library\Infrastructure\BookRental\Mappers\BookRentalMapper;
use Illuminate\Support\Facades\DB;

class BookRentalService
{
    public function __construct(
        private readonly BookRentalRepositoryInterface $rentRepository,
        private readonly BookRepositoryInterface $bookRepository,
        private readonly BookRentalMapper $rentMapper
    ) {}

    /**
     * Rents a book for a user based on the provided rental data.
     * Uses optimistic locking to handle concurrent access safely.
     *
     * @param RentABookDTO $dto Data transfer object containing rental details (e.g., user ID, book ID).
     * @return array An associative array containing details of the rented book.
     * @throws BookNotFoundException If the specified book is not found.
     * @throws BookNotAvailableForRentException If the book is not available for rent.
     * @throws ActiveRentalExistsException If there is an existing active rental for the book by the user.
     * @throws OptimisticLockException If concurrent modification detected after max retries.
     */
    public function rentBook(RentABookDTO $dto): array
    {
        $bookEntity = $this->bookRepository->findById($dto->bookId);

        if (!$bookEntity) {
            throw new BookNotFoundException('Book not found');
        }

        if (!$bookEntity->isAvailable()) {
            throw new BookNotAvailableForRentException('Book is currently unavailable');
        }

        // Check for existing active rental
        if ($this->rentRepository->hasActiveRentalForBook($dto->userId, $dto->bookId)) {
            throw new ActiveRentalExistsException('You already have an active rental for this book');
        }

        $version = $bookEntity->getVersion();
        $bookId = $dto->bookId;

        $rentalEntity = $this->rentMapper->fromRentDTOToEntity($dto);

        $savedEntity = DB::transaction(function () use ($bookId, $version, $rentalEntity) {

            $success = $this->bookRepository->decrementAvailabilityWithLock(
                $bookId,
                $version
            );

            if (!$success) {
                throw new OptimisticLockException(
                    'Book',
                    $bookId,
                    $version
                );
            }

            return $this->rentRepository->save($rentalEntity);
        });

        return $this->rentMapper->entityToArray($savedEntity);
    }

    /**
     * Retrieves rental information for a given rental ID and user ID.
     *
     * @param int $rentalId The ID of the rental to retrieve.
     * @param int $userId The ID of the user associated with the rental.
     * @return array An associative array containing the rental details with book info.
     * @throws RentalNotFoundException|\App\Library\Application\Exceptions\BookNotFoundException If the rental is not found for the specified user.
     */
    public function getRental(int $rentalId, int $userId): array
    {
        $result = $this->rentRepository->findByIdAndUserWithBookId($rentalId, $userId);

        if (!$result) {
            throw new RentalNotFoundException("Rental with ID {$rentalId} not found for user ID {$userId}");
        }

        $bookEntity = $this->bookRepository->findById($result['bookId']);
        
        if (!$bookEntity) {
            throw new BookNotFoundException('Associated book not found');
        }

        $dto = $this->rentMapper->toDTOWithBook($result['rental'], $bookEntity);

        return $this->rentMapper->dtoToArray($dto);
    }

    /**
     * Extends the rental period for a given rental entity.
     *
     * @param int $rentalId The unique identifier of the rental to be extended.
     * @param ExtendRentalDTO $dto Data transfer object containing information required for the extension.
     * @return array An array representation of the updated rental entity.
     * @throws RentalNotFoundException If the rental is not found for the specified user.
     */
    public function extendRental(int $rentalId, ExtendRentalDTO $dto): array
    {
        $rentalEntity = $this->findRentalOrFail($rentalId, $dto->userId);

        $newPeriod = $rentalEntity->getRentalPeriod()->extend($dto->extensionDays);
        $updatedEntity = $this->rentRepository->extendRental($rentalId, $newPeriod);

        return $this->rentMapper->entityToArray($updatedEntity);
    }

    /**
     * Updates the reading progress for a given rental.
     *
     * @param int $rentalId The unique identifier of the rental to update.
     * @param UpdateReadingProgressDTO $dto Data transfer object containing user ID and new progress value.
     * @return array An array representation of the updated rental entity.
     * @throws RentalNotFoundException If the rental is not found for the specified user.
     */
    public function updateReadingProgress(int $rentalId, UpdateReadingProgressDTO $dto): array
    {
        $rentalEntity = $this->findRentalOrFail($rentalId, $dto->userId);

        $newProgress = new ReadingProgress($dto->progress);
        $updatedEntity = $this->rentRepository->updateReadingProgress($rentalId, $newProgress);

        return $this->rentMapper->entityToArray($updatedEntity);
    }

    /**
     * Returns a rented book and increments the book's availability.
     * Uses optimistic locking to handle concurrent access safely.
     *
     * @param int $rentalId The unique identifier of the rental to return.
     * @param ReturnBookDTO $dto Data transfer object containing user ID.
     *
     * @return array An array representation of the updated rental entity.
     * @throws RentalNotFoundException If the rental is not found for the specified user.
     * @throws \DomainException If the rental is already returned.
     * @throws OptimisticLockException If concurrent modification detected after max retries.
     * @throws BookNotFoundException
     */
    public function returnBook(int $rentalId, ReturnBookDTO $dto): array
    {
        $rentalEntity = $this->findRentalOrFail($rentalId, $dto->userId);

        $bookId = $rentalEntity->getBookId();

        $bookEntity = $this->bookRepository->findById($bookId);

        if (!$bookEntity) {
            throw new BookNotFoundException('Book not found');
        }

        $version = $bookEntity->getVersion();

        $updatedEntity = DB::transaction(function () use ($rentalId, $dto, $bookId, $version) {
            $updatedEntity = $this->rentRepository->returnBook($rentalId);

            $success = $this->bookRepository->incrementAvailabilityWithLock(
                $bookId,
                $version
            );

            if (!$success) {
                throw new OptimisticLockException(
                    'Book',
                    $bookId,
                    $version
                );
            }

            return $updatedEntity;
        });

        return $this->rentMapper->entityToArray($updatedEntity);
    }

    /**
     * Retrieves a rental entity for a given rental ID and user ID or throws an exception if not found.
     *
     * @param int $rentalId The unique identifier of the rental to be retrieved.
     * @param int $userId The unique identifier of the user associated with the rental.
     * @return BookRentalEntity The rental entity associated with the provided rental ID and user ID.
     * @throws RentalNotFoundException If the rental entity does not exist for the specified rental ID and user ID.
     */
    private function findRentalOrFail(int $rentalId, int $userId): BookRentalEntity
    {
        $rentalEntity = $this->rentRepository->findByIdAndUser($rentalId, $userId);

        if (!$rentalEntity) {
            throw new RentalNotFoundException("Rental with ID {$rentalId} not found for user ID {$userId}");
        }

        return $rentalEntity;
    }
}