<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\NoteRepositoryInterface;
use App\Repositories\NoteRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any repository bindings.
     */
    public function register()
    {
        // Bind interface to concrete repository
        $this->app->bind(
            NoteRepositoryInterface::class,
            NoteRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        //
    }
}
