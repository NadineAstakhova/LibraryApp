<?php

namespace App\Library\Infrastructure\Book\Database\Repositories;

use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Domain\Book\ValueObjects\BookSearchCriteria;
use App\Library\Infrastructure\Book\Database\Models\Book;
use App\Library\Infrastructure\Book\Mappers\BookMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentBookRepository implements BookRepositoryInterface
{
    public function __construct(
        private readonly BookMapper $mapper
    ) {}

    /**
     * @param int $id The unique identifier of the book to find.
     * @return BookEntity|null The book entity if found, or null if no book exists with the specified ID.
     */
    public function findById(int $id): ?BookEntity
    {
        $bookModel = Book::find($id);
        return $bookModel ? $this->mapper->fromEloquentModelToEntity($bookModel) : null;
    }

    /**
     * @param BookSearchCriteria $criteria Search criteria including filters, sort, and pagination
     * @return LengthAwarePaginator Paginated results with Book entities
     */
    public function search(BookSearchCriteria $criteria): LengthAwarePaginator
    {
        $query = $this->buildSearchQuery($criteria);
        
        $paginator = $query->paginate(
            perPage: $criteria->getPerPage(),
            page: $criteria->getPage()
        );

        $paginator->getCollection()->transform(
            fn (Book $model) => $this->mapper->fromEloquentModelToEntity($model)
        );
        
        return $paginator;
    }

    /**
     * @param BookSearchCriteria $criteria
     * @return Builder
     */
    private function buildSearchQuery(BookSearchCriteria $criteria): Builder
    {
        $query = Book::query();

        $this->applyFilters($query, $criteria);
        $this->applySorting($query, $criteria);

        return $query;
    }

    /**
     * @param Builder $query
     * @param BookSearchCriteria $criteria
     */
    private function applyFilters(Builder $query, BookSearchCriteria $criteria): void
    {
        if ($criteria->hasTitleFilter()) {
            $query->where('title', 'like', '%' . $criteria->getTitle() . '%');
        }

        if ($criteria->hasAuthorFilter()) {
            $query->where('author', 'like', '%' . $criteria->getAuthor() . '%');
        }

        if ($criteria->hasGenreFilter()) {
            $query->where('genre', $criteria->getGenre());
        }

        if ($criteria->isAvailableOnly()) {
            $query->where('available_copies', '>', 0);
        }
    }

    /**
     * @param Builder $query
     * @param BookSearchCriteria $criteria
     */
    private function applySorting(Builder $query, BookSearchCriteria $criteria): void
    {
        $sortBy = $criteria->getSortBy() ?? 'title';
        $query->orderBy($sortBy, $criteria->getSortDirection());
    }

    /**
     * @param BookEntity $entity The book entity to create
     * @return BookEntity The created book with ID and version
     */
    public function create(BookEntity $entity): BookEntity
    {
        $book = new Book();
        $book->title = $entity->getTitle();
        $book->author = $entity->getAuthor();
        $book->isbn = $entity->getIsbn()->getValue();
        $book->genre = $entity->getGenre();
        $book->description = $entity->getDescription();
        $book->total_copies = $entity->getTotalCopies();
        $book->available_copies = $entity->getTotalCopies(); // New books have all copies available
        $book->publication_year = $entity->getPublicationYear();
        $book->version = 1;
        $book->save();

        return $this->mapper->fromEloquentModelToEntity($book);
    }

    /**
     * @param BookEntity $entity The book entity with updated values
     * @param int $expectedVersion The expected version for optimistic locking
     * @return BookEntity|null The updated book, or null if version mismatch
     */
    public function updateWithLock(BookEntity $entity, int $expectedVersion): ?BookEntity
    {
        $affectedRows = DB::table('books')
            ->where('id', $entity->getId())
            ->where('version', $expectedVersion)
            ->whereNull('deleted_at')
            ->update([
                'title' => $entity->getTitle(),
                'author' => $entity->getAuthor(),
                'isbn' => $entity->getIsbn()->getValue(),
                'genre' => $entity->getGenre(),
                'description' => $entity->getDescription(),
                'total_copies' => $entity->getTotalCopies(),
                'available_copies' => $entity->getAvailableCopies(),
                'publication_year' => $entity->getPublicationYear(),
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);

        if ($affectedRows === 0) {
            return null;
        }

        return $this->findById($entity->getId());
    }

    /**
     * @param int $id The book ID
     * @param int $expectedVersion The expected version for optimistic locking
     * @return bool True if deleted, false if version mismatch or not found
     */
    public function deleteWithLock(int $id, int $expectedVersion): bool
    {
        $affectedRows = DB::table('books')
            ->where('id', $id)
            ->where('version', $expectedVersion)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);

        return $affectedRows > 0;
    }

    /**
     * @param int $bookId The book ID
     *
     * @return int Number of active rentals
     */
    public function countActiveRentals(int $bookId): int
    {
        return DB::table('book_rents')
            ->where('book_id', $bookId)
            ->where('status', 'active')
            ->count();
    }

    /**
     * @param int $bookId The book ID
     * @param int $expectedVersion The expected version for optimistic locking
     * @return bool True if successful, false if version mismatch (concurrent modification)
     */
    public function decrementAvailabilityWithLock(int $bookId, int $expectedVersion): bool
    {
        $affectedRows = DB::table('books')
            ->where('id', $bookId)
            ->where('version', $expectedVersion)
            ->where('available_copies', '>', 0)
            ->whereNull('deleted_at')
            ->update([
                'available_copies' => DB::raw('available_copies - 1'),
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);

        return $affectedRows > 0;
    }

    /**
     * @param int $bookId The book ID
     * @param int $expectedVersion The expected version for optimistic locking
     * @return bool True if successful, false if version mismatch (concurrent modification)
     */
    public function incrementAvailabilityWithLock(int $bookId, int $expectedVersion): bool
    {
        $affectedRows = DB::table('books')
            ->where('id', $bookId)
            ->where('version', $expectedVersion)
            ->whereColumn('available_copies', '<', 'total_copies')
            ->whereNull('deleted_at')
            ->update([
                'available_copies' => DB::raw('available_copies + 1'),
                'version' => DB::raw('version + 1'),
                'updated_at' => now(),
            ]);

        return $affectedRows > 0;
    }
}