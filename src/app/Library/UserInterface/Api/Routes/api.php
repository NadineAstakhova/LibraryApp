<?php

declare(strict_types=1);

use App\Library\UserInterface\Api\Controller\Book\BookController;
use App\Library\UserInterface\Api\Controller\BookRental\BookRentalController;
use App\Library\UserInterface\Api\Controller\User\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    //public routes
    Route::get('/books', [BookController::class, 'index'])
        ->name('books.index');

    Route::get('/books/{id}', [BookController::class, 'show'])
        ->name('books.show');

    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->name('auth.register');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('auth.login');
    });

    // Protected Routes (Requires Authentication)
    Route::middleware(['jwt.auth'])->group(function () {

        // Auth Routes (Authenticated)
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('auth.logout');
        });

        // Book Rental Routes
        Route::prefix('rentals')->group(function () {
            // Rent a book
            Route::post('/', [BookRentalController::class, 'rent'])
                ->name('rentals.rent');

            // Actions with a rental
            Route::prefix('{rentalId}')->group(function () {
                // Get specific rental details
                Route::get('/', [BookRentalController::class, 'show'])
                    ->name('rentals.show');
            });
        });


    });
});