<?php

namespace App\Library\Application\BookRental\Services;

use App\Library\Application\BookRental\DTOs\ExtendRentalDTO;
use App\Library\Application\BookRental\DTOs\RentABookDTO;
use App\Library\Application\BookRental\Exceptions\BookNotAvailableForRentException;
use App\Library\Application\Exceptions\ActiveRentalExistsException;
use App\Library\Application\Exceptions\BookNotFoundException;
use App\Library\Application\Exceptions\RentalNotFoundException;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\Repositories\BookRentalRepositoryInterface;
use App\Library\Infrastructure\BookRental\Mappers\BookRentalMapper;

class BookRentalService
{
    public function __construct(
        private readonly BookRentalRepositoryInterface $rentRepository,
        private readonly BookRepositoryInterface $bookRepository,
        private readonly BookRentalMapper $rentMapper
    ) {}

    /**
     * Rents a book for a user based on the provided rental data.
     *
     * @param RentABookDTO $dto Data transfer object containing rental details (e.g., user ID, book ID).
     *
     * @return array An associative array containing details of the rented book.
     * @throws BookNotFoundException If the specified book is not found.
     * @throws BookNotAvailableForRentException If the book is not available for rent.
     * @throws ActiveRentalExistsException If there is an existing active rental for the book by the user.
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

        $rentalEntity = $this->rentMapper->fromRentDTOToEntity($dto);

        $this->bookRepository->decrementAvailability($dto->bookId);

        $savedEntity = $this->rentRepository->save($rentalEntity);

        return $this->rentMapper->entityToArray($savedEntity);
    }

    /**
     * Retrieves rental information for a given rental ID and user ID.
     *
     * @param int $rentalId The ID of the rental to retrieve.
     * @param int $userId The ID of the user associated with the rental.
     *
     * @return array An associative array containing the rental details.
     * @throws RentalNotFoundException If the rental is not found for the specified user.
     */
    public function getRental(int $rentalId, int $userId): array
    {
        $rentalEntity = $this->rentRepository->findByIdAndUserWithBookInfo($rentalId, $userId);

        if (!$rentalEntity) {
            throw new RentalNotFoundException("Rental with ID {$rentalId} not found for user ID {$userId}");
        }

        return $this->rentMapper->dtoToArray($rentalEntity);
    }

    /**
     * Extends the rental period for a given rental entity.
     *
     * @param int $rentalId The unique identifier of the rental to be extended.
     * @param ExtendRentalDTO $dto Data transfer object containing information required for the extension, such as user ID and extension
     *     days.
     *
     * @return array An array representation of the updated rental entity.
     * @throws \App\Library\Application\Exceptions\RentalNotFoundException
     */
    public function extendRental(int $rentalId, ExtendRentalDTO $dto): array
    {
        $rentalEntity = $this->findRentalOrFail($rentalId, $dto->userId);

        $extendedEntity = $rentalEntity->extend($dto->extensionDays);
        $savedEntity = $this->rentRepository->save($extendedEntity);

        return $this->rentMapper->entityToArray($savedEntity);
    }

    /**
     * Retrieves a rental entity for a given rental ID and user ID or throws an exception if not found.
     *
     * @param int $rentalId The unique identifier of the rental to be retrieved.
     * @param int $userId The unique identifier of the user associated with the rental.
     *
     * @return BookRentalEntity The rental entity associated with the provided rental ID and user ID.
     * @throws RentalNotFoundException If the rental entity does not exist for the specified rental ID and user ID.
     */
    private function findRentalOrFail(int $rentalId, int $userId): BookRentalEntity
    {
        $rentalEntity = $this->rentRepository->findByIdAndUserEntity($rentalId, $userId);

        if (!$rentalEntity) {
            throw new RentalNotFoundException("Rental with ID {$rentalId} not found for user ID {$userId}");
        }

        return $rentalEntity;
    }

}