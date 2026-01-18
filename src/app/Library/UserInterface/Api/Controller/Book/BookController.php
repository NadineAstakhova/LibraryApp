<?php

namespace App\Library\UserInterface\Api\Controller\Book;

use App\Http\Controllers\Controller;
use App\Library\Application\Book\DTOs\SearchBookDTO;
use App\Library\Application\Book\Services\BookService;
use App\Library\Infrastructure\Book\Mappers\BookMapper;
use App\Library\UserInterface\Api\Requests\Book\SearchBookRequest;
use App\Library\UserInterface\Base\ApiResponseJson;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    public function __construct(
        private readonly BookService $bookService
    ) {}

    public function index(SearchBookRequest $request): JsonResponse
    {
        $dto = new SearchBookDTO(
            title: $request->input('title'),
            author: $request->input('author'),
            genre: $request->input('genre'),
            availableOnly: $request->boolean('available_only'),
            sortBy: $request->input('sort_by'),
            sortDirection: $request->input('sort_direction', 'asc'),
            perPage: $request->input('per_page', 15),
        );

        $books = $this->bookService->searchBooks($dto);
//todo, test
        return response()->json([
            'data' => $books->items(),
            'meta' => [
                'current_page' => $books->currentPage(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'total' => $books->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $book = $this->bookService->getBookById($id);

        if (!$book) {
            return ApiResponseJson::errorJsonResponse('Book not found', 404);
        }

        return ApiResponseJson::successJsonResponse(BookMapper::toArray($book));
    }
}