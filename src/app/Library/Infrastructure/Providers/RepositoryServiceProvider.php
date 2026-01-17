<?php

namespace App\Library\Infrastructure\Providers;

use App\Library\Domain\User\Repositories\UserRepositoryInterface;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}