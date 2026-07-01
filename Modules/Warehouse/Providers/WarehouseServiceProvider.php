<?php

namespace Modules\Warehouse\Providers;

use Illuminate\Support\ServiceProvider;

class WarehouseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Warehouse\Services\WarehouseService::class);
    }

    public function boot(): void
    {
        //
    }
}
