<?php

namespace App\Library\Application\BookRental\Services;

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
        $rentalEntity = $this->rentRepository->findByIdAndUser($rentalId, $userId);

        if (!$rentalEntity) {
            throw new RentalNotFoundException("Rental with ID {$rentalId} not found for user ID {$userId}");
        }

        return $this->rentMapper->dtoToArray($rentalEntity);
    }

}