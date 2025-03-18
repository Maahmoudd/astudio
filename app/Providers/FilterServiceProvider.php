<?php

namespace App\Providers;

use App\Filters\AttributeFilter;
use App\Filters\BasicConditionFilter;
use App\Filters\ExistsFilter;
use App\Filters\FilterRegistry;
use App\Filters\HasAnyFilter;
use App\Filters\IsAnyFilter;
use Illuminate\Support\ServiceProvider;

class FilterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton(FilterRegistry::class, function ($app) {
            $registry = new FilterRegistry();

            // Register all filter types in order of specificity
            // More specific filters should be registered first
            $registry->register(new AttributeFilter());
            $registry->register(new HasAnyFilter());
            $registry->register(new IsAnyFilter());
            $registry->register(new ExistsFilter());

            $registry->register(new BasicConditionFilter());

            return $registry;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
