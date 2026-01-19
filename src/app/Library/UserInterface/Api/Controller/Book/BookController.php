<?php

namespace App\Library\UserInterface\Api\Controller\Book;

use App\Http\Controllers\Controller;
use App\Library\Application\Book\DTOs\CreateBookDTO;
use App\Library\Application\Book\DTOs\DeleteBookDTO;
use App\Library\Application\Book\DTOs\SearchBookDTO;
use App\Library\Application\Book\DTOs\UpdateBookDTO;
use App\Library\Application\Book\Services\BookService;
use App\Library\Application\Exceptions\BookHasActiveRentalsException;
use App\Library\Application\Exceptions\BookNotFoundException;
use App\Library\Application\Exceptions\OptimisticLockException;
use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Infrastructure\Book\Mappers\BookMapper;
use App\Library\UserInterface\Api\Requests\Book\CreateBookRequest;
use App\Library\UserInterface\Api\Requests\Book\DeleteBookRequest;
use App\Library\UserInterface\Api\Requests\Book\SearchBookRequest;
use App\Library\UserInterface\Api\Requests\Book\UpdateBookRequest;
use App\Library\UserInterface\Base\ApiResponseJson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService,
        private readonly BookMapper $bookMapper
    ) {}

    #[OA\Get(
        path: "/api/v1/books",
        summary: "List books",
        description: "Get a paginated list of books with optional filtering and sorting",
        operationId: "listBooks",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(name: "title", in: "query", description: "Filter by title (partial match)", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "author", in: "query", description: "Filter by author (partial match)", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "genre", in: "query", description: "Filter by genre (exact match)", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "available_only", in: "query", description: "Show only available books", required: false, schema: new OA\Schema(type: "boolean", default: false)),
            new OA\Parameter(name: "sort_by", in: "query", description: "Sort field", required: false, schema: new OA\Schema(type: "string", enum: ["title", "author", "genre", "publication_year", "available_copies", "created_at"], default: "title")),
            new OA\Parameter(name: "sort_direction", in: "query", description: "Sort direction", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "asc")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page (1-100)", required: false, schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100, default: 15)),
            new OA\Parameter(name: "page", in: "query", description: "Page number", required: false, schema: new OA\Schema(type: "integer", minimum: 1, default: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Book")),
                        new OA\Property(property: "meta", ref: "#/components/schemas/PaginationMeta"),
                        new OA\Property(property: "links", ref: "#/components/schemas/PaginationLinks")
                    ]
                )
            )
        ]
    )]
    public function index(SearchBookRequest $request): JsonResponse
    {
        $dto = $this->createSearchDTO($request);
        $books = $this->bookService->searchBooks($dto);

        return response()->json([
            'data' => collect($books->items())->map(
                fn (BookEntity $book) => $this->bookMapper->toArray($book)
            ),
            'meta' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
            'links' => [
                'first' => $books->url(1),
                'last' => $books->url($books->lastPage()),
                'prev' => $books->previousPageUrl(),
                'next' => $books->nextPageUrl(),
            ],
        ]);
    }

    #[OA\Get(
        path: "/api/v1/books/{id}",
        summary: "Get book details",
        description: "Get detailed information about a specific book",
        operationId: "getBook",
        tags: ["Books"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Book ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/Book")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Book not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $book = $this->bookService->getBookById($id);

        if (!$book) {
            return ApiResponseJson::errorJsonResponse('Book not found', Response::HTTP_NOT_FOUND);
        }

        return ApiResponseJson::successJsonResponse($this->bookMapper->toArray($book));
    }

    #[OA\Post(
        path: "/api/v1/books",
        summary: "Create a new book",
        description: "Create a new book (Admin only)",
        operationId: "createBook",
        tags: ["Books"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "author", "isbn", "genre", "total_copies"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "The Great Gatsby"),
                    new OA\Property(property: "author", type: "string", example: "F. Scott Fitzgerald"),
                    new OA\Property(property: "isbn", type: "string", example: "978-0-7432-7356-5"),
                    new OA\Property(property: "genre", type: "string", example: "Fiction"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "A novel about the American Dream"),
                    new OA\Property(property: "total_copies", type: "integer", minimum: 1, example: 5),
                    new OA\Property(property: "publication_year", type: "integer", nullable: true, example: 1925)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Book created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/Book")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden - Admin access required"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(CreateBookRequest $request): JsonResponse
    {
        $dto = new CreateBookDTO(
            title: $request->input('title'),
            author: $request->input('author'),
            isbn: $request->input('isbn'),
            genre: $request->input('genre'),
            description: $request->input('description'),
            totalCopies: (int) $request->input('total_copies'),
            publicationYear: $request->input('publication_year') ? (int) $request->input('publication_year') : null,
        );

        $book = $this->bookService->createBook($dto);

        return ApiResponseJson::successJsonResponse(
            $this->bookMapper->toArray($book),
            Response::HTTP_CREATED
        );
    }

    #[OA\Put(
        path: "/api/v1/books/{id}",
        summary: "Update a book",
        description: "Update an existing book (Admin only). Uses optimistic locking.",
        operationId: "updateBook",
        tags: ["Books"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Book ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["version"],
                properties: [
                    new OA\Property(property: "version", type: "integer", description: "Current version for optimistic locking", example: 1),
                    new OA\Property(property: "title", type: "string", example: "The Great Gatsby - Updated"),
                    new OA\Property(property: "author", type: "string", example: "F. Scott Fitzgerald"),
                    new OA\Property(property: "isbn", type: "string", example: "978-0-7432-7356-5"),
                    new OA\Property(property: "genre", type: "string", example: "Classic Fiction"),
                    new OA\Property(property: "description", type: "string", nullable: true, example: "Updated description"),
                    new OA\Property(property: "total_copies", type: "integer", minimum: 1, example: 10),
                    new OA\Property(property: "publication_year", type: "integer", nullable: true, example: 1925)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Book updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/Book")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden - Admin access required"),
            new OA\Response(response: 404, description: "Book not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 409, description: "Conflict - Version mismatch", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function update(UpdateBookRequest $request, int $id): JsonResponse
    {
        try {
            $dto = new UpdateBookDTO(
                id: $id,
                version: (int) $request->input('version'),
                title: $request->input('title'),
                author: $request->input('author'),
                isbn: $request->input('isbn'),
                genre: $request->input('genre'),
                description: $request->input('description'),
                totalCopies: $request->input('total_copies') !== null ? (int) $request->input('total_copies') : null,
                publicationYear: $request->input('publication_year') !== null ? (int) $request->input('publication_year') : null,
            );

            $book = $this->bookService->updateBook($dto);

            return ApiResponseJson::successJsonResponse($this->bookMapper->toArray($book));
        } catch (BookNotFoundException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (OptimisticLockException $e) {
            return ApiResponseJson::errorJsonResponse(
                $e->getMessage(),
                Response::HTTP_CONFLICT
            );
        }
    }

    #[OA\Delete(
        path: "/api/v1/books/{id}",
        summary: "Delete a book",
        description: "Soft delete a book (Admin only). Uses optimistic locking. Cannot delete books with active rentals.",
        operationId: "deleteBook",
        tags: ["Books"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", description: "Book ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["version"],
                properties: [
                    new OA\Property(property: "version", type: "integer", description: "Current version for optimistic locking", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Book deleted successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", type: "object", properties: [
                            new OA\Property(property: "message", type: "string", example: "Book deleted successfully")
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden - Admin access required"),
            new OA\Response(response: 404, description: "Book not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 409, description: "Conflict - Book has active rentals or version mismatch", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function destroy(DeleteBookRequest $request, int $id): JsonResponse
    {
        try {
            $dto = new DeleteBookDTO(
                id: $id,
                version: (int) $request->input('version'),
            );

            $this->bookService->deleteBook($dto);

            return ApiResponseJson::successJsonResponse(
                ['message' => 'Book deleted successfully'],
                Response::HTTP_OK
            );
        } catch (BookNotFoundException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (BookHasActiveRentalsException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_CONFLICT);
        } catch (OptimisticLockException $e) {
            return ApiResponseJson::errorJsonResponse(
                $e->getMessage(),
                Response::HTTP_CONFLICT
            );
        }
    }

    /**
     * Create SearchBookDTO from request.
     *
     * @param SearchBookRequest $request
     * @return SearchBookDTO
     */
    private function createSearchDTO(SearchBookRequest $request): SearchBookDTO
    {
        return new SearchBookDTO(
            title: $request->input('title'),
            author: $request->input('author'),
            genre: $request->input('genre'),
            availableOnly: $request->boolean('available_only', false),
            sortBy: $request->input('sort_by', 'title'),
            sortDirection: $request->input('sort_direction', 'asc'),
            perPage: (int) $request->input('per_page', 15),
            page: (int) $request->input('page', 1),
        );
    }
}
