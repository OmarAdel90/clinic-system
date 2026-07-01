<?php

namespace Modules\Supplier\Providers;

use Illuminate\Support\ServiceProvider;

class SupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Supplier\Services\SupplierService::class);
        $this->app->singleton(\Modules\Supplier\Services\SupplierPaymentService::class);
    }

    public function boot(): void
    {
        //
    }
}
