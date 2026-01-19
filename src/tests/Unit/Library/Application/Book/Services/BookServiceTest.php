<?php

namespace Tests\Unit\Library\Application\Book\Services;

use App\Library\Application\Book\DTOs\CreateBookDTO;
use App\Library\Application\Book\DTOs\DeleteBookDTO;
use App\Library\Application\Book\DTOs\SearchBookDTO;
use App\Library\Application\Book\DTOs\UpdateBookDTO;
use App\Library\Application\Book\Services\BookService;
use App\Library\Application\Exceptions\BookHasActiveRentalsException;
use App\Library\Application\Exceptions\BookNotFoundException;
use App\Library\Application\Exceptions\OptimisticLockException;
use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Domain\Book\ValueObjects\BookSearchCriteria;
use App\Library\Domain\Book\ValueObjects\ISBN;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class BookServiceTest extends TestCase
{
    private BookRepositoryInterface|MockInterface $bookRepository;
    private BookService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookRepository = Mockery::mock(BookRepositoryInterface::class);
        $this->service = new BookService($this->bookRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== searchBooks Tests ====================

    public function test_search_books_returns_paginated_results(): void
    {
        // Arrange
        $dto = new SearchBookDTO(
            title: 'Test',
            author: null,
            genre: null,
            availableOnly: false,
            sortBy: 'title',
            sortDirection: 'asc',
            perPage: 15,
            page: 1
        );

        $bookEntity = $this->createBookEntity();
        $paginator = new LengthAwarePaginator(
            items: [$bookEntity],
            total: 1,
            perPage: 15,
            currentPage: 1
        );

        $this->bookRepository
            ->shouldReceive('search')
            ->with(Mockery::type(BookSearchCriteria::class))
            ->once()
            ->andReturn($paginator);

        // Act
        $result = $this->service->searchBooks($dto);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertCount(1, $result->items());
    }

    public function test_search_books_with_filters(): void
    {
        // Arrange
        $dto = new SearchBookDTO(
            title: 'Great',
            author: 'Fitzgerald',
            genre: 'Fiction',
            availableOnly: true,
            sortBy: 'author',
            sortDirection: 'desc',
            perPage: 10,
            page: 2
        );

        $paginator = new LengthAwarePaginator(
            items: [],
            total: 0,
            perPage: 10,
            currentPage: 2
        );

        $this->bookRepository
            ->shouldReceive('search')
            ->with(Mockery::on(function (BookSearchCriteria $criteria) {
                return $criteria->getTitle() === 'Great'
                    && $criteria->getAuthor() === 'Fitzgerald'
                    && $criteria->getGenre() === 'Fiction'
                    && $criteria->isAvailableOnly() === true
                    && $criteria->getSortBy() === 'author'
                    && $criteria->getSortDirection() === 'desc'
                    && $criteria->getPerPage() === 10
                    && $criteria->getPage() === 2;
            }))
            ->once()
            ->andReturn($paginator);

        // Act
        $result = $this->service->searchBooks($dto);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }

    // ==================== getBookById Tests ====================

    public function test_get_book_by_id_returns_book_when_found(): void
    {
        // Arrange
        $bookId = 1;
        $bookEntity = $this->createBookEntity(id: $bookId);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($bookEntity);

        // Act
        $result = $this->service->getBookById($bookId);

        // Assert
        $this->assertInstanceOf(BookEntity::class, $result);
        $this->assertEquals($bookId, $result->getId());
    }

    public function test_get_book_by_id_returns_null_when_not_found(): void
    {
        // Arrange
        $bookId = 999;

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn(null);

        // Act
        $result = $this->service->getBookById($bookId);

        // Assert
        $this->assertNull($result);
    }

    // ==================== createBook Tests ====================

    public function test_create_book_successfully(): void
    {
        // Arrange
        $dto = new CreateBookDTO(
            title: 'The Great Gatsby',
            author: 'F. Scott Fitzgerald',
            isbn: '978-0-7432-7356-5',
            genre: 'Fiction',
            description: 'A novel about the American Dream',
            totalCopies: 5,
            publicationYear: 1925
        );

        $createdEntity = $this->createBookEntity(
            id: 1,
            title: 'The Great Gatsby',
            author: 'F. Scott Fitzgerald',
            totalCopies: 5,
            availableCopies: 5
        );

        $this->bookRepository
            ->shouldReceive('create')
            ->with(Mockery::on(function (BookEntity $entity) use ($dto) {
                return $entity->getTitle() === $dto->title
                    && $entity->getAuthor() === $dto->author
                    && $entity->getIsbn()->getValue() === $dto->isbn
                    && $entity->getGenre() === $dto->genre
                    && $entity->getDescription() === $dto->description
                    && $entity->getTotalCopies() === $dto->totalCopies
                    && $entity->getAvailableCopies() === $dto->totalCopies
                    && $entity->getPublicationYear() === $dto->publicationYear;
            }))
            ->once()
            ->andReturn($createdEntity);

        // Act
        $result = $this->service->createBook($dto);

        // Assert
        $this->assertInstanceOf(BookEntity::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('The Great Gatsby', $result->getTitle());
        $this->assertEquals(5, $result->getTotalCopies());
        $this->assertEquals(5, $result->getAvailableCopies());
    }

    public function test_create_book_without_optional_fields(): void
    {
        // Arrange
        $dto = new CreateBookDTO(
            title: 'Minimal Book',
            author: 'Unknown Author',
            isbn: '978-1-234-56789-0',
            genre: 'General',
            description: null,
            totalCopies: 1,
            publicationYear: null
        );

        $createdEntity = $this->createBookEntity(
            id: 2,
            title: 'Minimal Book',
            author: 'Unknown Author',
            totalCopies: 1,
            availableCopies: 1
        );

        $this->bookRepository
            ->shouldReceive('create')
            ->with(Mockery::type(BookEntity::class))
            ->once()
            ->andReturn($createdEntity);

        // Act
        $result = $this->service->createBook($dto);

        // Assert
        $this->assertInstanceOf(BookEntity::class, $result);
        $this->assertEquals(2, $result->getId());
    }

    // ==================== updateBook Tests ====================

    public function test_update_book_successfully(): void
    {
        // Arrange
        $bookId = 1;
        $existingBook = $this->createBookEntity(
            id: $bookId,
            title: 'Old Title',
            author: 'Old Author',
            version: 1
        );

        $dto = new UpdateBookDTO(
            id: $bookId,
            version: 1,
            title: 'New Title',
            author: 'New Author',
            isbn: null,
            genre: null,
            description: null,
            totalCopies: null,
            publicationYear: null
        );

        $updatedBook = $this->createBookEntity(
            id: $bookId,
            title: 'New Title',
            author: 'New Author',
            version: 2
        );

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($existingBook);

        $this->bookRepository
            ->shouldReceive('updateWithLock')
            ->with(Mockery::type(BookEntity::class), 1)
            ->once()
            ->andReturn($updatedBook);

        // Act
        $result = $this->service->updateBook($dto);

        // Assert
        $this->assertInstanceOf(BookEntity::class, $result);
        $this->assertEquals('New Title', $result->getTitle());
        $this->assertEquals('New Author', $result->getAuthor());
        $this->assertEquals(2, $result->getVersion());
    }

    public function test_update_book_throws_exception_when_not_found(): void
    {
        // Arrange
        $dto = new UpdateBookDTO(
            id: 999,
            version: 1,
            title: 'New Title',
            author: null,
            isbn: null,
            genre: null,
            description: null,
            totalCopies: null,
            publicationYear: null
        );

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->service->updateBook($dto);
    }

    public function test_update_book_throws_optimistic_lock_exception_on_version_mismatch(): void
    {
        // Arrange
        $bookId = 1;
        $existingBook = $this->createBookEntity(id: $bookId, version: 2);

        $dto = new UpdateBookDTO(
            id: $bookId,
            version: 1, // Outdated version
            title: 'New Title',
            author: null,
            isbn: null,
            genre: null,
            description: null,
            totalCopies: null,
            publicationYear: null
        );

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($existingBook);

        $this->bookRepository
            ->shouldReceive('updateWithLock')
            ->with(Mockery::type(BookEntity::class), 1)
            ->once()
            ->andReturn(null); // Version mismatch

        // Assert
        $this->expectException(OptimisticLockException::class);

        // Act
        $this->service->updateBook($dto);
    }

    public function test_update_book_adjusts_available_copies_when_total_copies_reduced(): void
    {
        // Arrange
        $bookId = 1;
        $existingBook = $this->createBookEntity(
            id: $bookId,
            totalCopies: 10,
            availableCopies: 7, // 3 copies are rented
            version: 1
        );

        $dto = new UpdateBookDTO(
            id: $bookId,
            version: 1,
            title: null,
            author: null,
            isbn: null,
            genre: null,
            description: null,
            totalCopies: 5, // Reduce to 5 total
            publicationYear: null
        );

        $updatedBook = $this->createBookEntity(
            id: $bookId,
            totalCopies: 5,
            availableCopies: 2, // 5 - 3 rented = 2 available
            version: 2
        );

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($existingBook);

        $this->bookRepository
            ->shouldReceive('updateWithLock')
            ->with(Mockery::on(function (BookEntity $entity) {
                // New available = 5 - 3 = 2
                return $entity->getTotalCopies() === 5
                    && $entity->getAvailableCopies() === 2;
            }), 1)
            ->once()
            ->andReturn($updatedBook);

        // Act
        $result = $this->service->updateBook($dto);

        // Assert
        $this->assertEquals(5, $result->getTotalCopies());
        $this->assertEquals(2, $result->getAvailableCopies());
    }

    public function test_update_book_available_copies_not_negative_when_total_less_than_rented(): void
    {
        // Arrange
        $bookId = 1;
        $existingBook = $this->createBookEntity(
            id: $bookId,
            totalCopies: 10,
            availableCopies: 2, // 8 copies are rented
            version: 1
        );

        $dto = new UpdateBookDTO(
            id: $bookId,
            version: 1,
            title: null,
            author: null,
            isbn: null,
            genre: null,
            description: null,
            totalCopies: 5, // Reduce to 5, but 8 are rented
            publicationYear: null
        );

        $updatedBook = $this->createBookEntity(
            id: $bookId,
            totalCopies: 5,
            availableCopies: 0, // max(0, 5 - 8) = 0
            version: 2
        );

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($existingBook);

        $this->bookRepository
            ->shouldReceive('updateWithLock')
            ->with(Mockery::on(function (BookEntity $entity) {
                // Available should be 0, not negative
                return $entity->getTotalCopies() === 5
                    && $entity->getAvailableCopies() === 0;
            }), 1)
            ->once()
            ->andReturn($updatedBook);

        // Act
        $result = $this->service->updateBook($dto);

        // Assert
        $this->assertEquals(5, $result->getTotalCopies());
        $this->assertEquals(0, $result->getAvailableCopies());
    }

    // ==================== deleteBook Tests ====================

    public function test_delete_book_successfully(): void
    {
        // Arrange
        $bookId = 1;
        $existingBook = $this->createBookEntity(id: $bookId, version: 1);

        $dto = new DeleteBookDTO(id: $bookId, version: 1);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($existingBook);

        $this->bookRepository
            ->shouldReceive('countActiveRentals')
            ->with($bookId)
            ->once()
            ->andReturn(0);

        $this->bookRepository
            ->shouldReceive('deleteWithLock')
            ->with($bookId, 1)
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->service->deleteBook($dto);

        // Assert
        $this->assertTrue($result);
    }

    public function test_delete_book_throws_exception_when_not_found(): void
    {
        // Arrange
        $dto = new DeleteBookDTO(id: 999, version: 1);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        // Assert
        $this->expectException(BookNotFoundException::class);

        // Act
        $this->service->deleteBook($dto);
    }

    public function test_delete_book_throws_exception_when_has_active_rentals(): void
    {
        // Arrange
        $bookId = 1;
        $existingBook = $this->createBookEntity(id: $bookId, version: 1);

        $dto = new DeleteBookDTO(id: $bookId, version: 1);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($existingBook);

        $this->bookRepository
            ->shouldReceive('countActiveRentals')
            ->with($bookId)
            ->once()
            ->andReturn(3); // 3 active rentals

        // Assert
        $this->expectException(BookHasActiveRentalsException::class);
        $this->expectExceptionMessage("Cannot delete book with ID {$bookId} because it has 3 active rental(s)");

        // Act
        $this->service->deleteBook($dto);
    }

    public function test_delete_book_throws_optimistic_lock_exception_on_version_mismatch(): void
    {
        // Arrange
        $bookId = 1;
        $existingBook = $this->createBookEntity(id: $bookId, version: 2);

        $dto = new DeleteBookDTO(id: $bookId, version: 1); // Outdated version

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($existingBook);

        $this->bookRepository
            ->shouldReceive('countActiveRentals')
            ->with($bookId)
            ->once()
            ->andReturn(0);

        $this->bookRepository
            ->shouldReceive('deleteWithLock')
            ->with($bookId, 1)
            ->once()
            ->andReturn(false); // Version mismatch

        // Assert
        $this->expectException(OptimisticLockException::class);

        // Act
        $this->service->deleteBook($dto);
    }

    // ==================== Helper Methods ====================

    private function createBookEntity(
        ?int $id = 1,
        string $title = 'Test Book',
        string $author = 'Test Author',
        string $isbn = '978-3-16-148410-0',
        string $genre = 'Fiction',
        ?string $description = 'A test book',
        int $totalCopies = 5,
        int $availableCopies = 3,
        int $version = 1,
        ?int $publicationYear = 2024
    ): BookEntity {
        return new BookEntity(
            id: $id,
            title: $title,
            author: $author,
            isbn: new ISBN($isbn),
            genre: $genre,
            description: $description,
            totalCopies: $totalCopies,
            availableCopies: $availableCopies,
            version: $version,
            publicationYear: $publicationYear
        );
    }
}
