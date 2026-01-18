<?php

namespace App\Library\UserInterface\Api\Controller\BookRental;

use App\Http\Controllers\Controller;
use App\Library\Application\BookRental\DTOs\RentABookDTO;
use App\Library\Application\BookRental\Exceptions\BookNotAvailableForRentException;
use App\Library\Application\BookRental\Services\BookRentalService;
use App\Library\Application\Exceptions\ActiveRentalExistsException;
use App\Library\Application\Exceptions\BookNotFoundException;
use App\Library\Application\Exceptions\RentalNotFoundException;
use App\Library\UserInterface\Api\Requests\BookRental\RentABookRequest;
use App\Library\UserInterface\Base\ApiResponseJson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BookRentalController extends Controller
{
    public function __construct(
        private readonly BookRentalService $rentService
    ) {}

    /**
     * Handles the rental process for a book.
     *
     * @param RentABookRequest $request The request object containing details for renting a book,
     *                                  such as user ID, book ID, and rental duration.
     *
     * @return JsonResponse The JSON response indicating the outcome of the rental process,
     *                      which could include success or error details.
     */
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
        }

        return ApiResponseJson::successJsonResponse($rental);
    }

    /**
     * Retrieves and displays details of a specific rental.
     *
     * @param int $rentalId The unique identifier of the rental to be retrieved.
     *
     * @return JsonResponse The JSON response containing the rental details on success,
     *                      or an error message with the appropriate status code on failure.
     */
    public function show(int $rentalId): JsonResponse
    {
        try {
            $rental = $this->rentService->getRental($rentalId, auth('api')->id());
        } catch (RentalNotFoundException $e) {
            return ApiResponseJson::errorJsonResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return ApiResponseJson::successJsonResponse($rental);
    }
}