<?php

namespace App\Library\Application\Book\Services;

use App\Library\Application\Book\DTOs\CreateBookDTO;
use App\Library\Application\Book\DTOs\DeleteBookDTO;
use App\Library\Application\Book\DTOs\SearchBookDTO;
use App\Library\Application\Book\DTOs\UpdateBookDTO;
use App\Library\Application\Exceptions\BookHasActiveRentalsException;
use App\Library\Application\Exceptions\BookNotFoundException;
use App\Library\Application\Exceptions\OptimisticLockException;
use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Domain\Book\ValueObjects\BookSearchCriteria;
use App\Library\Domain\Book\ValueObjects\ISBN;
use Illuminate\Pagination\LengthAwarePaginator;

class BookService
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository
    ) {}

    /**
     * @param SearchBookDTO $dto Search parameters from the request
     * @return LengthAwarePaginator Paginated book entities
     */
    public function searchBooks(SearchBookDTO $dto): LengthAwarePaginator
    {
        $criteria = $this->createCriteriaFromDTO($dto);
        
        return $this->bookRepository->search($criteria);
    }

    /**
     * @param int $id Book ID
     * @return BookEntity|null
     */
    public function getBookById(int $id): ?BookEntity
    {
        return $this->bookRepository->findById($id);
    }

    /**
     * @param CreateBookDTO $dto Book creation data
     * @return BookEntity The created book
     */
    public function createBook(CreateBookDTO $dto): BookEntity
    {
        $entity = new BookEntity(
            id: null,
            title: $dto->title,
            author: $dto->author,
            isbn: new ISBN($dto->isbn),
            genre: $dto->genre,
            description: $dto->description,
            totalCopies: $dto->totalCopies,
            availableCopies: $dto->totalCopies,
            version: 1,
            publicationYear: $dto->publicationYear,
        );

        return $this->bookRepository->create($entity);
    }

    /**
     * @param UpdateBookDTO $dto Book update data including version
     * @return BookEntity The updated book
     * @throws BookNotFoundException If book not found
     * @throws OptimisticLockException If version mismatch (concurrent modification)
     */
    public function updateBook(UpdateBookDTO $dto): BookEntity
    {
        $existingBook = $this->bookRepository->findById($dto->id);
        
        if (!$existingBook) {
            throw new BookNotFoundException($dto->id);
        }

        $updatedEntity = $this->applyUpdatesToEntity($existingBook, $dto);

        $result = $this->bookRepository->updateWithLock($updatedEntity, $dto->version);
        
        if ($result === null) {
            throw new OptimisticLockException('Book', $dto->id, $dto->version);
        }

        return $result;
    }

    /**
     * @param DeleteBookDTO $dto Book deletion data including version
     * @return bool True if deleted
     * @throws BookNotFoundException If book not found
     * @throws BookHasActiveRentalsException If book has active rentals
     * @throws OptimisticLockException If version mismatch (concurrent modification)
     */
    public function deleteBook(DeleteBookDTO $dto): bool
    {
        $existingBook = $this->bookRepository->findById($dto->id);
        
        if (!$existingBook) {
            throw new BookNotFoundException($dto->id);
        }

        $activeRentals = $this->bookRepository->countActiveRentals($dto->id);
        if ($activeRentals > 0) {
            throw new BookHasActiveRentalsException($dto->id, $activeRentals);
        }

        $deleted = $this->bookRepository->deleteWithLock($dto->id, $dto->version);
        
        if (!$deleted) {
            throw new OptimisticLockException('Book', $dto->id, $dto->version);
        }

        return true;
    }

    /**
     * @param SearchBookDTO $dto
     * @return BookSearchCriteria
     */
    private function createCriteriaFromDTO(SearchBookDTO $dto): BookSearchCriteria
    {
        return new BookSearchCriteria(
            title: $dto->title,
            author: $dto->author,
            genre: $dto->genre,
            availableOnly: $dto->availableOnly,
            sortBy: $dto->sortBy,
            sortDirection: $dto->sortDirection,
            perPage: $dto->perPage,
            page: $dto->page,
        );
    }

    /**
     * @param BookEntity $entity Existing entity
     * @param UpdateBookDTO $dto Update data
     * @return BookEntity Updated entity
     */
    private function applyUpdatesToEntity(BookEntity $entity, UpdateBookDTO $dto): BookEntity
    {
        if ($dto->title !== null) {
            $entity->setTitle($dto->title);
        }
        if ($dto->author !== null) {
            $entity->setAuthor($dto->author);
        }
        if ($dto->isbn !== null) {
            $entity->setIsbn(new ISBN($dto->isbn));
        }
        if ($dto->genre !== null) {
            $entity->setGenre($dto->genre);
        }
        if ($dto->description !== null) {
            $entity->setDescription($dto->description);
        }
        if ($dto->totalCopies !== null) {
            $currentTotal = $entity->getTotalCopies();
            $currentAvailable = $entity->getAvailableCopies();
            $rentedCopies = $currentTotal - $currentAvailable;

            $newAvailable = max(0, $dto->totalCopies - $rentedCopies);
            
            $entity->setTotalCopies($dto->totalCopies);
            $entity->setAvailableCopies($newAvailable);
        }
        if ($dto->publicationYear !== null) {
            $entity->setPublicationYear($dto->publicationYear);
        }

        return $entity;
    }
}
