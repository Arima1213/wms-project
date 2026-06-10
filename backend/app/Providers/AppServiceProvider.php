<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

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
        Event::listen(
            \App\Events\LowStockDetected::class,
            \App\Listeners\DispatchWebhookOnAlert::class,
        );
        Event::listen(
            \App\Events\ExpiringProductDetected::class,
            \App\Listeners\DispatchWebhookOnAlert::class,
        );
        Event::listen(
            \App\Events\InboundOverdueDetected::class,
            \App\Listeners\DispatchWebhookOnAlert::class,
        );
        Event::listen(
            \App\Events\OutboundOverdueDetected::class,
            \App\Listeners\DispatchWebhookOnAlert::class,
        );
    }
}
