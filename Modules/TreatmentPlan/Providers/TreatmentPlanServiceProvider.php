<?php

namespace Modules\TreatmentPlan\Providers;

use Illuminate\Support\ServiceProvider;

class TreatmentPlanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\TreatmentPlan\Services\TreatmentPlanService::class);
    }

    public function boot(): void
    {
        //
    }
}
