<?php

declare(strict_types=1);

use App\Library\UserInterface\Api\Controller\User\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Authentication Routes
    Route::prefix('auth')->group(function () {
//        Route::post('/register', [RegisterController::class, 'register'])
//            ->name('auth.register');

        Route::post('/login', [AuthController::class, 'login'])
            ->name('auth.login');
    });

    Route::middleware(['auth:api'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])
                ->name('auth.logout');
        });

    });

});