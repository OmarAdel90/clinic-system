<?php

namespace Modules\Pharmaceutical\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Pharmaceutical\Services\PharmaceuticalService;

class PharmaceuticalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PharmaceuticalService::class);
    }

    public function boot(): void
    {
        //
    }
}
