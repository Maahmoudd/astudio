<?php

namespace App\Providers;

use App\Repositories\IRepository;
use App\Repositories\Repository;
use App\Services\IJobFilterService;
use App\Services\JobFilterService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(IJobFilterService::class, JobFilterService::class);
        $this->app->bind(IRepository::class, Repository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
