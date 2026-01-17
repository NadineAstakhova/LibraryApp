<?php

declare(strict_types=1);

use App\Library\UserInterface\Api\Controller\Book\BookController;
use App\Library\UserInterface\Api\Controller\User\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])
            ->name('auth.register');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('auth.login');
    });

    Route::middleware(['jwt.auth'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('auth.logout');
        });



    });

    Route::get('/books', [BookController::class, 'index'])
        ->name('books.index');

});