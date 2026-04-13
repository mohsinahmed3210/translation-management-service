<?php

namespace App\Providers;

use App\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\Contracts\TranslationRepositoryInterface;
use App\Repositories\TagRepository;
use App\Repositories\TranslationRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TranslationRepositoryInterface::class, TranslationRepository::class);
        $this->app->bind(TagRepositoryInterface::class, TagRepository::class);
    }

    public function boot(): void
    {
        //
    }
}
