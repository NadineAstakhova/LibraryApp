<?php

declare(strict_types=1);

use App\Library\UserInterface\Api\Controller\Book\BookController;
use App\Library\UserInterface\Api\Controller\BookRental\BookRentalController;
use App\Library\UserInterface\Api\Controller\User\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['throttle:api'])->group(function () {

    // ==========================================
    // PUBLIC ROUTES (No authentication required)
    // ==========================================
    
    // Book listing and details (public access)
    Route::get('/books', [BookController::class, 'index'])
        ->name('books.index');

    Route::get('/books/{id}', [BookController::class, 'show'])
        ->name('books.show');

    // Authentication Routes (stricter rate limiting)
    Route::prefix('auth')->middleware(['throttle:auth'])->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->name('auth.register');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('auth.login');
    });

    // ==========================================
    // PROTECTED ROUTES (Requires Authentication)
    // ==========================================
    Route::middleware(['jwt.auth', 'throttle:authenticated'])->group(function () {

        // Auth Routes (Authenticated)
        Route::prefix('auth')->group(function () {
            // Logout
            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('auth.logout');

            // Refresh token
            Route::post('/refresh', [AuthController::class, 'refresh'])
                ->name('auth.refresh');

            // Get current user profile
            Route::get('/me', [AuthController::class, 'me'])
                ->name('auth.me');

            // Update password
            Route::put('/password', [AuthController::class, 'updatePassword'])
                ->name('auth.updatePassword');

            // Update profile (name)
            Route::put('/profile', [AuthController::class, 'updateProfile'])
                ->name('auth.updateProfile');
        });

        // Book Rental Routes (Any authenticated user)
        Route::prefix('rentals')->group(function () {
            // Rent a book (stricter rate limiting for rental creation)
            Route::post('/', [BookRentalController::class, 'rent'])
                ->middleware(['throttle:rentals'])
                ->name('rentals.rent');

            // Actions with a rental
            Route::prefix('{rentalId}')->group(function () {
                // Get specific rental details
                Route::get('/', [BookRentalController::class, 'show'])
                    ->name('rentals.show');

                // Extend rental period
                Route::post('/extend', [BookRentalController::class, 'extend'])
                    ->name('rentals.extend');

                // Update reading progress
                Route::patch('/progress', [BookRentalController::class, 'updateProgress'])
                    ->name('rentals.updateProgress');

                // Return a book
                Route::post('/return', [BookRentalController::class, 'returnBook'])
                    ->name('rentals.return');
            });
        });

        // ==========================================
        // ADMIN ROUTES (Requires Admin Role)
        // ==========================================
        Route::middleware(['admin', 'throttle:admin'])->group(function () {
            
            // Book Management (Admin only)
            Route::prefix('books')->group(function () {
                // Create a new book
                Route::post('/', [BookController::class, 'store'])
                    ->name('books.store');

                // Update an existing book
                Route::put('/{id}', [BookController::class, 'update'])
                    ->name('books.update');

                // Delete a book
                Route::delete('/{id}', [BookController::class, 'destroy'])
                    ->name('books.destroy');
            });
        });
    });
});