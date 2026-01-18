<?php

namespace App\Library\Infrastructure\Providers;

use App\Library\Domain\Book\Repositories\BookRepositoryInterface;
use App\Library\Domain\BookRental\Repositories\BookRentalRepositoryInterface;
use App\Library\Domain\User\Repositories\UserRepositoryInterface;
use App\Library\Infrastructure\Book\Database\Repositories\EloquentBookRepository;
use App\Library\Infrastructure\BookRental\Database\Repositories\EloquentBookRentalRepository;
use App\Library\Infrastructure\User\Database\Repositories\EloquentUserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(BookRepositoryInterface::class, EloquentBookRepository::class);
        $this->app->bind(BookRentalRepositoryInterface::class, EloquentBookRentalRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}