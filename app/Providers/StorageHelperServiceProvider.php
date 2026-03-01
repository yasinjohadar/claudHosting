<?php

namespace App\Providers;

use App\Services\Storage\StorageHelperService;
use Illuminate\Support\ServiceProvider;

class StorageHelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(StorageHelperService::class, function ($app) {
            return new StorageHelperService(
                $app->make(\App\Services\Storage\AppStorageManager::class)
            );
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
