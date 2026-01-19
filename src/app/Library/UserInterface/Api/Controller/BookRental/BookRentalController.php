<?php

namespace App\Library\UserInterface\Api\Controller\BookRental;

use App\Http\Controllers\Controller;
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
use App\Library\UserInterface\Api\Requests\BookRental\ExtendRentalRequest;
use App\Library\UserInterface\Api\Requests\BookRental\RentABookRequest;
use App\Library\UserInterface\Api\Requests\BookRental\UpdateReadingProgressRequest;
use App\Library\UserInterface\Base\ApiResponseJson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class BookRentalController extends Controller
{
    public function __construct(
        private readonly BookRentalService $rentService
    ) {}

    #[OA\Post(
        path: "/api/v1/rentals",
        summary: "Rent a book",
        description: "Create a new book rental for the authenticated user",
        operationId: "rentBook",
        tags: ["Rentals"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["book_id"],
                properties: [
                    new OA\Property(property: "book_id", type: "integer", example: 1),
                    new OA\Property(property: "rental_days", type: "integer", minimum: 1, maximum: 90, default: 14, example: 14)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Book rented successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/BookRental")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Book not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 409, description: "Conflict - Book not available or user already has active rental", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 429, description: "Too many requests - Rate limit exceeded")
        ]
    )]
    public function rent(RentABookRequest $request): JsonResponse
    {
        $rentBookDTO = new RentABookDTO(
            userId: auth('api')->id(),
            bookId: $request->integer('book_id'),
            rentalDays: $request->integer('rental_days', 14),
        );

        try {
            $rental = $this->rentService->rentBook($rentBookDTO);
        } catch (BookNotAvailableForRentException|ActiveRentalExistsException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_CONFLICT);
        } catch (BookNotFoundException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (OptimisticLockException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiResponseJson::successJsonResponse($rental);
    }

    #[OA\Get(
        path: "/api/v1/rentals/{rentalId}",
        summary: "Get rental details",
        description: "Get detailed information about a specific rental including book details",
        operationId: "getRental",
        tags: ["Rentals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "rentalId", in: "path", description: "Rental ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/BookRentalWithBook")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Rental not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function show(int $rentalId): JsonResponse
    {
        try {
            $rental = $this->rentService->getRental($rentalId, auth('api')->id());
        } catch (RentalNotFoundException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return ApiResponseJson::successJsonResponse($rental);
    }

    #[OA\Post(
        path: "/api/v1/rentals/{rentalId}/extend",
        summary: "Extend rental period",
        description: "Extend the rental period for an active rental (max 5 extensions)",
        operationId: "extendRental",
        tags: ["Rentals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "rentalId", in: "path", description: "Rental ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "days", type: "integer", minimum: 1, maximum: 90, default: 14, example: 14)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Rental extended successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/BookRental")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Rental not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function extend(ExtendRentalRequest $request, int $rentalId): JsonResponse
    {
        $dto = new ExtendRentalDTO(
            userId: auth('api')->id(),
            extensionDays: $request->integer('days', 14),
        );

        try {
            $rental = $this->rentService->extendRental($rentalId, $dto);
        } catch (RentalNotFoundException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return ApiResponseJson::successJsonResponse($rental);
    }

    #[OA\Patch(
        path: "/api/v1/rentals/{rentalId}/progress",
        summary: "Update reading progress",
        description: "Update the reading progress percentage for a rental",
        operationId: "updateReadingProgress",
        tags: ["Rentals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "rentalId", in: "path", description: "Rental ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["progress"],
                properties: [
                    new OA\Property(property: "progress", type: "integer", minimum: 0, maximum: 100, example: 50)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Progress updated successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/BookRental")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Rental not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function updateProgress(UpdateReadingProgressRequest $request, int $rentalId): JsonResponse
    {
        $dto = new UpdateReadingProgressDTO(
            userId: auth('api')->id(),
            progress: $request->integer('progress'),
        );

        try {
            $rental = $this->rentService->updateReadingProgress($rentalId, $dto);
        } catch (RentalNotFoundException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return ApiResponseJson::successJsonResponse($rental);
    }

    #[OA\Post(
        path: "/api/v1/rentals/{rentalId}/return",
        summary: "Return a book",
        description: "Return a rented book and mark the rental as completed",
        operationId: "returnBook",
        tags: ["Rentals"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "rentalId", in: "path", description: "Rental ID", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Book returned successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "data", ref: "#/components/schemas/BookRental")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Rental not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 409, description: "Conflict - Rental already returned or concurrent modification", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse"))
        ]
    )]
    public function returnBook(int $rentalId): JsonResponse
    {
        $dto = new ReturnBookDTO(
            userId: auth('api')->id(),
        );

        try {
            $rental = $this->rentService->returnBook($rentalId, $dto);
        } catch (RentalNotFoundException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (\DomainException|OptimisticLockException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiResponseJson::successJsonResponse($rental);
    }
}
