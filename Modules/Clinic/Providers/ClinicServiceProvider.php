<?php

namespace Modules\Clinic\Providers;

use Illuminate\Support\ServiceProvider;

class ClinicServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Clinic\Services\ClinicService::class);
    }

    public function boot(): void
    {
        //
    }
}
