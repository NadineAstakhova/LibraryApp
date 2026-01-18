<?php

namespace App\Library\Infrastructure\BookRental\Mappers;

use App\Library\Application\BookRental\DTOs\BookRentalWithBookDTO;
use App\Library\Application\BookRental\DTOs\RentABookDTO;
use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\ValueObjects\ReadingProgress;
use App\Library\Domain\BookRental\ValueObjects\RentalPeriod;
use App\Library\Domain\BookRental\ValueObjects\Status;
use App\Library\Infrastructure\BookRental\Database\Models\BookRental as BookRentalEloquentModel;

class BookRentalMapper
{
    /**
     * @param \App\Library\Application\BookRental\DTOs\RentABookDTO $rentDTO
     *
     * @return \App\Library\Domain\BookRental\Entities\BookRental
     */
    public function fromRentDTOToEntity(RentABookDTO $rentDTO): BookRentalEntity
    {
        return new BookRentalEntity(
            id: null,
            userId: $rentDTO->userId,
            bookId: $rentDTO->bookId,
            rentalPeriod: RentalPeriod::createNew($rentDTO->rentalDays),
            status: new Status(Status::ACTIVE),
            readingProgress: new ReadingProgress(0),
            extensionCount: 0,
        );
    }

    /**
     * @param \App\Library\Domain\BookRental\Entities\BookRental $bookRentalEntity
     *
     * @return \App\Library\Infrastructure\BookRental\Database\Models\BookRental
     */
    public function fromEntityToModel(BookRentalEntity $bookRentalEntity): BookRentalEloquentModel
    {
        $bookRentalModel = new BookRentalEloquentModel();

        if ($bookRentalEntity->getId()) {
            $bookRentalModel->id = $bookRentalEntity->getId();
            $bookRentalModel->exists = true;
        }

        $bookRentalModel->user_id = $bookRentalEntity->getUserId();
        $bookRentalModel->book_id = $bookRentalEntity->getBookId();
        $bookRentalModel->rented_at = $bookRentalEntity->getRentalPeriod()->getRentedAt();
        $bookRentalModel->due_date = $bookRentalEntity->getRentalPeriod()->getDueDate();
        $bookRentalModel->returned_at = $bookRentalEntity->getRentalPeriod()->getReturnedAt();
        $bookRentalModel->status = $bookRentalEntity->getStatus()->getValue();
        $bookRentalModel->reading_progress = $bookRentalEntity->getReadingProgress()->getValue();
        $bookRentalModel->extension_count = $bookRentalEntity->getExtensionCount();

        return $bookRentalModel;
    }

    /**
     * @param \App\Library\Infrastructure\BookRental\Database\Models\BookRental $bookRental
     *
     * @return \App\Library\Domain\BookRental\Entities\BookRental
     */
    public function fromModelToEntity(BookRentalEloquentModel $bookRental): BookRentalEntity
    {
        return new BookRentalEntity(
            id: $bookRental->id,
            userId: $bookRental->user_id,
            bookId: $bookRental->book_id,
            rentalPeriod: new RentalPeriod(
                rentedAt: $bookRental->rented_at,
                dueDate: $bookRental->due_date,
                returnedAt: $bookRental->returned_at,
            ),
            status: new Status($bookRental->status),
            readingProgress: new ReadingProgress($bookRental->reading_progress),
            extensionCount: $bookRental->extension_count,
        );
    }

    /**
     * @param \App\Library\Domain\BookRental\Entities\BookRental $bookRental
     * @param \App\Library\Domain\Book\Entities\Book $book
     *
     * @return \App\Library\Application\BookRental\DTOs\BookRentalWithBookDTO
     */
    public function toDTOWithBook(BookRentalEntity $bookRental, BookEntity $book): BookRentalWithBookDTO
    {
        return new BookRentalWithBookDTO(
            id: $bookRental->getId(),
            userId: $bookRental->getUserId(),
            bookId: $bookRental->getBookId(),
            rentedAt: $bookRental->getRentalPeriod()->getRentedAt(),
            dueDate: $bookRental->getRentalPeriod()->getDueDate(),
            returnedAt: $bookRental->getRentalPeriod()->getReturnedAt(),
            status: $bookRental->getStatus()->getValue(),
            readingProgress: $bookRental->getReadingProgress()->getValue(),
            extensionCount: $bookRental->getExtensionCount(),
            daysRemaining: $bookRental->getRentalPeriod()->daysRemaining(),
            canExtend: $bookRental->canExtend(),
            isOverdue: $bookRental->getRentalPeriod()->isOverdue(),
            book: $book->toArray(),
        );
    }

    /**
     * @param \App\Library\Domain\BookRental\Entities\BookRental $entity
     *
     * @return array
     */
    public function entityToArray(BookRentalEntity $entity): array
    {
        return $entity->toArray();
    }

    /**
     * @param \App\Library\Application\BookRental\DTOs\BookRentalWithBookDTO $bookRentalDTO
     *
     * @return \App\Library\Application\BookRental\DTOs\BookRentalWithBookDTO[]
     */
    public function dtoToArray(BookRentalWithBookDTO $bookRentalDTO): array
    {
        return array($bookRentalDTO);
    }

}