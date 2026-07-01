<?php

namespace Modules\Lead\Providers;

use Illuminate\Support\ServiceProvider;

class LeadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Lead\Services\LeadService::class);
    }

    public function boot(): void
    {
        //
    }
}
