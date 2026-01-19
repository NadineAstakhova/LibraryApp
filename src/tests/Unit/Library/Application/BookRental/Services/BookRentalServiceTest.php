<?php

namespace Tests\Unit\Library\Application\BookRental\Services;

use App\Library\Application\BookRental\DTOs\BookRentalWithBookDTO;
use App\Library\Application\BookRental\DTOs\ExtendRentalDTO;
use App\Library\Application\BookRental\DTOs\RentABookDTO;
use App\Library\Application\BookRental\DTOs\ReturnBookDTO;
use App\Library\Application\BookRental\DTOs\UpdateReadingProgressDTO;
use App\Library\Application\BookRental\Services\BookRentalService;
use App\Library\Application\Exceptions\ActiveRentalExistsException;
use App\Library\Application\Exceptions\BookNotAvailableForRentException;
use App\Library\Application\Exceptions\BookNotFoundException;
use App\Library\Application\Exceptions\OptimisticLockException;
use App\Library\Application\Exceptions\RentalNotFoundException;
use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Domain\Book\ValueObjects\ISBN;
use App\Library\Domain\BookRental\Entities\BookRental as BookRentalEntity;
use App\Library\Domain\BookRental\Repositories\BookRentalRepositoryInterface;
use App\Library\Domain\BookRental\ValueObjects\ReadingProgress;
use App\Library\Domain\BookRental\ValueObjects\RentalPeriod;
use App\Library\Domain\BookRental\ValueObjects\Status;
use App\Library\Infrastructure\Book\Mappers\BookMapper;
use App\Library\Infrastructure\BookRental\Mappers\BookRentalMapper;
use Carbon\Carbon;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class BookRentalServiceTest extends TestCase
{
    private BookRentalRepositoryInterface|MockInterface $rentRepository;
    private BookRepositoryInterface|MockInterface $bookRepository;
    private BookRentalMapper|MockInterface $rentMapper;
    private BookMapper|MockInterface $bookMapper;
    private BookRentalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rentRepository = Mockery::mock(BookRentalRepositoryInterface::class);
        $this->bookRepository = Mockery::mock(BookRepositoryInterface::class);
        $this->rentMapper = Mockery::mock(BookRentalMapper::class);
        $this->bookMapper = Mockery::mock(BookMapper::class);

        $this->service = new BookRentalService(
            $this->rentRepository,
            $this->bookRepository,
            $this->rentMapper
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== rentBook Tests ====================

    public function test_rent_book_successfully(): void
    {
        // Arrange
        $dto = new RentABookDTO(userId: 1, bookId: 5, rentalDays: 14);
        
        $bookEntity = $this->createBookEntity(id: 5, availableCopies: 3, version: 1);
        $rentalEntity = $this->createRentalEntity(id: 1, userId: 1, bookId: 5);
        $expectedArray = ['id' => 1, 'user_id' => 1, 'book_id' => 5];

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($bookEntity);

        $this->rentRepository
            ->shouldReceive('hasActiveRentalForBook')
            ->with(1, 5)
            ->once()
            ->andReturn(false);

        $this->bookRepository
            ->shouldReceive('decrementAvailabilityWithLock')
            ->with(5, 1)
            ->once()
            ->andReturn(true);

        $this->rentMapper
            ->shouldReceive('fromRentDTOToEntity')
            ->with($dto)
            ->once()
            ->andReturn($rentalEntity);

        $this->rentRepository
            ->shouldReceive('save')
            ->with($rentalEntity)
            ->once()
            ->andReturn($rentalEntity);

        $this->rentMapper
            ->shouldReceive('entityToArray')
            ->with($rentalEntity)
            ->once()
            ->andReturn($expectedArray);

        // Act
        $result = $this->service->rentBook($dto);

        // Assert
        $this->assertEquals($expectedArray, $result);
    }

    public function test_rent_book_throws_exception_when_book_not_found(): void
    {
        // Arrange
        $dto = new RentABookDTO(userId: 1, bookId: 999, rentalDays: 14);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(999)
            ->once()
            ->andReturn(null);

        // Assert
        $this->expectException(BookNotFoundException::class);
        $this->expectExceptionMessage('Book not found');

        // Act
        $this->service->rentBook($dto);
    }

    public function test_rent_book_throws_exception_when_book_not_available(): void
    {
        // Arrange
        $dto = new RentABookDTO(userId: 1, bookId: 5, rentalDays: 14);
        
        $bookEntity = $this->createBookEntity(id: 5, availableCopies: 0, version: 1);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($bookEntity);

        // Assert
        $this->expectException(BookNotAvailableForRentException::class);
        $this->expectExceptionMessage('Book is currently unavailable');

        // Act
        $this->service->rentBook($dto);
    }

    public function test_rent_book_throws_exception_when_active_rental_exists(): void
    {
        // Arrange
        $dto = new RentABookDTO(userId: 1, bookId: 5, rentalDays: 14);
        
        $bookEntity = $this->createBookEntity(id: 5, availableCopies: 3, version: 1);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($bookEntity);

        $this->rentRepository
            ->shouldReceive('hasActiveRentalForBook')
            ->with(1, 5)
            ->once()
            ->andReturn(true);

        // Assert
        $this->expectException(ActiveRentalExistsException::class);
        $this->expectExceptionMessage('You already have an active rental for this book');

        // Act
        $this->service->rentBook($dto);
    }

    public function test_rent_book_throws_optimistic_lock_exception_on_version_mismatch(): void
    {
        // Arrange
        $dto = new RentABookDTO(userId: 1, bookId: 5, rentalDays: 14);
        
        $bookEntity = $this->createBookEntity(id: 5, availableCopies: 3, version: 1);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($bookEntity);

        $this->rentRepository
            ->shouldReceive('hasActiveRentalForBook')
            ->with(1, 5)
            ->once()
            ->andReturn(false);

        $this->bookRepository
            ->shouldReceive('decrementAvailabilityWithLock')
            ->with(5, 1)
            ->once()
            ->andReturn(false); // Version mismatch

        // Assert
        $this->expectException(OptimisticLockException::class);

        // Act
        $this->service->rentBook($dto);
    }

    // ==================== getRental Tests ====================

    public function test_get_rental_successfully(): void
    {
        // Arrange
        $rentalId = 1;
        $userId = 1;
        $bookId = 5;
        $rentalEntity = $this->createRentalEntity(id: $rentalId, userId: $userId, bookId: $bookId);
        $bookEntity = $this->createBookEntity(id: $bookId);
        $rentalDTO = $this->createBookRentalWithBookDTO();
        $expectedArray = ['id' => 1, 'user_id' => 1, 'book_id' => 5];

        $this->rentRepository
            ->shouldReceive('findByIdAndUserWithBookId')
            ->with($rentalId, $userId)
            ->once()
            ->andReturn(['rental' => $rentalEntity, 'bookId' => $bookId]);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with($bookId)
            ->once()
            ->andReturn($bookEntity);

        $this->rentMapper
            ->shouldReceive('toDTOWithBook')
            ->with($rentalEntity, $bookEntity)
            ->once()
            ->andReturn($rentalDTO);

        $this->rentMapper
            ->shouldReceive('dtoToArray')
            ->with($rentalDTO)
            ->once()
            ->andReturn($expectedArray);

        // Act
        $result = $this->service->getRental($rentalId, $userId);

        // Assert
        $this->assertEquals($expectedArray, $result);
    }

    public function test_get_rental_throws_exception_when_not_found(): void
    {
        // Arrange
        $rentalId = 999;
        $userId = 1;

        $this->rentRepository
            ->shouldReceive('findByIdAndUserWithBookId')
            ->with($rentalId, $userId)
            ->once()
            ->andReturn(null);

        // Assert
        $this->expectException(RentalNotFoundException::class);
        $this->expectExceptionMessage("Rental with ID {$rentalId} not found for user ID {$userId}");

        // Act
        $this->service->getRental($rentalId, $userId);
    }

    // ==================== extendRental Tests ====================

    public function test_extend_rental_successfully(): void
    {
        // Arrange
        $rentalId = 1;
        $dto = new ExtendRentalDTO(userId: 1, extensionDays: 7);
        
        $extendedEntity = $this->createRentalEntity(id: 1, userId: 1, bookId: 5, extensionCount: 1);
        $expectedArray = ['id' => 1, 'extension_count' => 1];

        // Mock the extend method on the entity
        $rentalEntityMock = Mockery::mock(BookRentalEntity::class);
        $rentalEntityMock->shouldReceive('extend')
            ->with(7)
            ->once()
            ->andReturn($extendedEntity);

        // Re-setup with mocked entity
        $this->rentRepository = Mockery::mock(BookRentalRepositoryInterface::class);
        $this->rentRepository
            ->shouldReceive('findByIdAndUser')
            ->with($rentalId, 1)
            ->once()
            ->andReturn($rentalEntityMock);

        $this->rentRepository
            ->shouldReceive('save')
            ->with($extendedEntity)
            ->once()
            ->andReturn($extendedEntity);

        $this->rentMapper
            ->shouldReceive('entityToArray')
            ->with($extendedEntity)
            ->once()
            ->andReturn($expectedArray);

        $service = new BookRentalService(
            $this->rentRepository,
            $this->bookRepository,
            $this->rentMapper,
            $this->bookMapper
        );

        // Act
        $result = $service->extendRental($rentalId, $dto);

        // Assert
        $this->assertEquals($expectedArray, $result);
    }

    public function test_extend_rental_throws_exception_when_not_found(): void
    {
        // Arrange
        $rentalId = 999;
        $dto = new ExtendRentalDTO(userId: 1, extensionDays: 7);

        $this->rentRepository
            ->shouldReceive('findByIdAndUser')
            ->with($rentalId, 1)
            ->once()
            ->andReturn(null);

        // Assert
        $this->expectException(RentalNotFoundException::class);

        // Act
        $this->service->extendRental($rentalId, $dto);
    }

    // ==================== updateReadingProgress Tests ====================

    public function test_update_reading_progress_successfully(): void
    {
        // Arrange
        $rentalId = 1;
        $dto = new UpdateReadingProgressDTO(userId: 1, progress: 50);
        
        $rentalEntity = $this->createRentalEntity(id: 1, userId: 1, bookId: 5);
        $updatedEntity = $this->createRentalEntity(id: 1, userId: 1, bookId: 5, readingProgress: 50);
        $expectedArray = ['id' => 1, 'reading_progress' => 50];

        $this->rentRepository
            ->shouldReceive('findByIdAndUser')
            ->with($rentalId, 1)
            ->once()
            ->andReturn($rentalEntity);

        $this->rentRepository
            ->shouldReceive('updateReadingProgress')
            ->with($rentalId, Mockery::type(ReadingProgress::class))
            ->once()
            ->andReturn($updatedEntity);

        $this->rentMapper
            ->shouldReceive('entityToArray')
            ->with($updatedEntity)
            ->once()
            ->andReturn($expectedArray);

        // Act
        $result = $this->service->updateReadingProgress($rentalId, $dto);

        // Assert
        $this->assertEquals($expectedArray, $result);
    }

    public function test_update_reading_progress_throws_exception_when_not_found(): void
    {
        // Arrange
        $rentalId = 999;
        $dto = new UpdateReadingProgressDTO(userId: 1, progress: 50);

        $this->rentRepository
            ->shouldReceive('findByIdAndUser')
            ->with($rentalId, 1)
            ->once()
            ->andReturn(null);

        // Assert
        $this->expectException(RentalNotFoundException::class);

        // Act
        $this->service->updateReadingProgress($rentalId, $dto);
    }

    // ==================== returnBook Tests ====================

    public function test_return_book_successfully(): void
    {
        // Arrange
        $rentalId = 1;
        $dto = new ReturnBookDTO(userId: 1);
        
        $rentalEntity = $this->createRentalEntity(id: 1, userId: 1, bookId: 5);
        $bookEntity = $this->createBookEntity(id: 5, availableCopies: 2, version: 1);
        $returnedEntity = $this->createRentalEntity(
            id: 1, 
            userId: 1, 
            bookId: 5, 
            status: Status::COMPLETED, 
            readingProgress: 100
        );
        $expectedArray = ['id' => 1, 'status' => 'completed', 'reading_progress' => 100];

        $this->rentRepository
            ->shouldReceive('findByIdAndUser')
            ->with($rentalId, 1)
            ->once()
            ->andReturn($rentalEntity);

        $this->rentRepository
            ->shouldReceive('returnBook')
            ->with($rentalId)
            ->once()
            ->andReturn($returnedEntity);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($bookEntity);

        $this->bookRepository
            ->shouldReceive('incrementAvailabilityWithLock')
            ->with(5, 1)
            ->once()
            ->andReturn(true);

        $this->rentMapper
            ->shouldReceive('entityToArray')
            ->with($returnedEntity)
            ->once()
            ->andReturn($expectedArray);

        // Act
        $result = $this->service->returnBook($rentalId, $dto);

        // Assert
        $this->assertEquals($expectedArray, $result);
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(100, $result['reading_progress']);
    }

    public function test_return_book_throws_exception_when_not_found(): void
    {
        // Arrange
        $rentalId = 999;
        $dto = new ReturnBookDTO(userId: 1);

        $this->rentRepository
            ->shouldReceive('findByIdAndUser')
            ->with($rentalId, 1)
            ->once()
            ->andReturn(null);

        // Assert
        $this->expectException(RentalNotFoundException::class);

        // Act
        $this->service->returnBook($rentalId, $dto);
    }

    public function test_return_book_throws_exception_when_already_returned(): void
    {
        // Arrange
        $rentalId = 1;
        $dto = new ReturnBookDTO(userId: 1);
        
        $rentalEntity = $this->createRentalEntity(id: 1, userId: 1, bookId: 5);

        $this->rentRepository
            ->shouldReceive('findByIdAndUser')
            ->with($rentalId, 1)
            ->once()
            ->andReturn($rentalEntity);

        $this->rentRepository
            ->shouldReceive('returnBook')
            ->with($rentalId)
            ->once()
            ->andThrow(new \DomainException('This rental has already been returned'));

        // Assert
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('This rental has already been returned');

        // Act
        $this->service->returnBook($rentalId, $dto);
    }

    public function test_return_book_throws_optimistic_lock_exception_on_version_mismatch(): void
    {
        // Arrange
        $rentalId = 1;
        $dto = new ReturnBookDTO(userId: 1);
        
        $rentalEntity = $this->createRentalEntity(id: 1, userId: 1, bookId: 5);
        $bookEntity = $this->createBookEntity(id: 5, availableCopies: 2, version: 1);
        $returnedEntity = $this->createRentalEntity(
            id: 1, 
            userId: 1, 
            bookId: 5, 
            status: Status::COMPLETED, 
            readingProgress: 100
        );

        $this->rentRepository
            ->shouldReceive('findByIdAndUser')
            ->with($rentalId, 1)
            ->once()
            ->andReturn($rentalEntity);

        $this->rentRepository
            ->shouldReceive('returnBook')
            ->with($rentalId)
            ->once()
            ->andReturn($returnedEntity);

        $this->bookRepository
            ->shouldReceive('findById')
            ->with(5)
            ->once()
            ->andReturn($bookEntity);

        $this->bookRepository
            ->shouldReceive('incrementAvailabilityWithLock')
            ->with(5, 1)
            ->once()
            ->andReturn(false); // Version mismatch

        // Assert
        $this->expectException(OptimisticLockException::class);

        // Act
        $this->service->returnBook($rentalId, $dto);
    }

    // ==================== Helper Methods ====================

    private function createBookEntity(
        int $id = 1,
        string $title = 'Test Book',
        string $author = 'Test Author',
        int $totalCopies = 5,
        int $availableCopies = 3,
        int $version = 1
    ): BookEntity {
        return new BookEntity(
            id: $id,
            title: $title,
            author: $author,
            isbn: new ISBN('978-3-16-148410-0'),
            genre: 'Fiction',
            description: 'A test book',
            totalCopies: $totalCopies,
            availableCopies: $availableCopies,
            version: $version,
            publicationYear: 2024
        );
    }

    private function createRentalEntity(
        int $id = 1,
        int $userId = 1,
        int $bookId = 1,
        string $status = Status::ACTIVE,
        int $readingProgress = 0,
        int $extensionCount = 0
    ): BookRentalEntity {
        $rentedAt = Carbon::now();
        $dueDate = $rentedAt->copy()->addDays(14);

        return new BookRentalEntity(
            id: $id,
            userId: $userId,
            bookId: $bookId,
            rentalPeriod: new RentalPeriod($rentedAt, $dueDate),
            status: new Status($status),
            readingProgress: new ReadingProgress($readingProgress),
            extensionCount: $extensionCount
        );
    }

    private function createBookRentalWithBookDTO(): BookRentalWithBookDTO
    {
        return new BookRentalWithBookDTO(
            id: 1,
            userId: 1,
            bookId: 5,
            rentedAt: Carbon::now(),
            dueDate: Carbon::now()->addDays(14),
            returnedAt: null,
            status: Status::ACTIVE,
            readingProgress: 0,
            extensionCount: 0,
            daysRemaining: 14,
            canExtend: true,
            isOverdue: false,
            book: [
                'id' => 5,
                'title' => 'Test Book',
                'author' => 'Test Author',
            ]
        );
    }
}
