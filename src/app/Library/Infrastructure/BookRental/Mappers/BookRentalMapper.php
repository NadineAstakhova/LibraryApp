<?php

namespace App\Library\Infrastructure\BookRental\Mappers;

use App\Library\Application\BookRental\DTOs\BookRentalDTO;
use App\Library\Application\BookRental\DTOs\RentABookDTO;
use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\ValueObjects\ReadingProgress;
use App\Library\Domain\BookRental\ValueObjects\RentalPeriod;
use App\Library\Domain\BookRental\ValueObjects\Status;
use App\Library\Infrastructure\BookRental\Database\Models\BookRental as BookRentalEloquentModel;

class BookRentalMapper
{
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

    public function fromEntityToDTO(BookRentalEntity $entity, ?array $bookData = null): BookRentalDTO
    {
        return new BookRentalDTO(
            id: $entity->getId(),
            userId: $entity->getUserId(),
            bookId: $entity->getBookId(),
            rentedAt: $entity->getRentalPeriod()->getRentedAt(),
            dueDate: $entity->getRentalPeriod()->getDueDate(),
            returnedAt: $entity->getRentalPeriod()->getReturnedAt(),
            status: $entity->getStatus()->getValue(),
            readingProgress: $entity->getReadingProgress()->getValue(),
            extensionCount: $entity->getExtensionCount(),
            daysRemaining: $entity->getRentalPeriod()->daysRemaining(),
            canExtend: $entity->canExtend(),
            isOverdue: $entity->getRentalPeriod()->isOverdue(),
            book: $bookData,
        );
    }

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

    public function toArray(BookRentalEntity $entity): array
    {
        return $entity->toArray();
    }

}