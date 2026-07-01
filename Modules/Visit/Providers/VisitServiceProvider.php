<?php

namespace Modules\Visit\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class VisitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Warehouse\Services\WarehouseService::class);
    }

    public function boot(): void
    {
        Event::listen(
            \Modules\Visit\Events\ReportCompleted::class,
            \Modules\CRM\Listeners\NotifyAgent::class,
        );
    }
}
