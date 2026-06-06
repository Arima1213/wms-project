<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\WarehouseService::class);
        $this->app->singleton(\App\Services\ProductService::class);
        $this->app->singleton(\App\Services\PlanogramService::class);
        $this->app->singleton(\App\Services\InventoryService::class);
    }

    public function boot(): void
    {
        //
    }
}
