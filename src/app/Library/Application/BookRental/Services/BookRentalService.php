<?php

namespace App\Library\Application\BookRental\Services;

use App\Library\Application\BookRental\DTOs\BookRentalDTO;
use App\Library\Application\BookRental\DTOs\RentABookDTO;
use App\Library\Application\BookRental\Exceptions\BookNotAvailableForRentException;
use App\Library\Application\Exceptions\ActiveRentalExistsException;
use App\Library\Application\Exceptions\BookNotFoundException;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Domain\BookRental\Repositories\BookRentalRepositoryInterface;
use App\Library\Infrastructure\Book\Mappers\BookMapper;
use App\Library\Infrastructure\BookRental\Mappers\BookRentalMapper;

class BookRentalService
{
    public function __construct(
        private readonly BookRentalRepositoryInterface $rentRepository,
        private readonly BookRepositoryInterface $bookRepository,
        private readonly BookMapper $bookMapper,
        private readonly BookRentalMapper $rentMapper
    ) {}

    /**
     * @throws \App\Library\Application\Exceptions\BookNotFoundException
     * @throws \App\Library\Application\Exceptions\ActiveRentalExistsException
     * @throws \App\Library\Application\BookRental\Exceptions\BookNotAvailableForRentException
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

        return $this->rentMapper->toArray($savedEntity);
    }
}