<?php

namespace App\Library\Domain\Book\Repositories;

use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\Book\ValueObjects\BookSearchCriteria;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookRepositoryInterface
{
    /**
     * @param int $id The book ID
     * @return BookEntity|null The book entity or null if not found
     */
    public function findById(int $id): ?BookEntity;
    
    /**
     * @param BookSearchCriteria $criteria Search criteria including filters, sort, and pagination
     * @return LengthAwarePaginator Paginated results
     */
    public function search(BookSearchCriteria $criteria): LengthAwarePaginator;
    
    /**
     * @param BookEntity $entity The book entity to create
     * @return BookEntity The created book with ID and version
     */
    public function create(BookEntity $entity): BookEntity;
    
    /**
     * @param BookEntity $entity The book entity with updated values
     * @param int $expectedVersion The expected version for optimistic locking
     * @return BookEntity|null The updated book, or null if version mismatch
     */
    public function updateWithLock(BookEntity $entity, int $expectedVersion): ?BookEntity;
    
    /**
     * @param int $id The book ID
     * @param int $expectedVersion The expected version for optimistic locking
     * @return bool True if deleted, false if version mismatch or not found
     */
    public function deleteWithLock(int $id, int $expectedVersion): bool;
    
    /**
     * @param int $bookId The book ID
     * @return int Number of active rentals
     */
    public function countActiveRentals(int $bookId): int;
    
    /**
     * @param int $bookId The book ID
     * @param int $expectedVersion The expected version for optimistic locking
     * @return bool True if successful, false if version mismatch (concurrent modification)
     */
    public function decrementAvailabilityWithLock(int $bookId, int $expectedVersion): bool;
    
    /**
     * @param int $bookId The book ID
     * @param int $expectedVersion The expected version for optimistic locking
     * @return bool True if successful, false if version mismatch (concurrent modification)
     */
    public function incrementAvailabilityWithLock(int $bookId, int $expectedVersion): bool;
}