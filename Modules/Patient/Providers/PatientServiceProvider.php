<?php

namespace Modules\Patient\Providers;

use Illuminate\Support\ServiceProvider;

class PatientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Patient\Services\MedicalRecordService::class);
        $this->app->singleton(\Modules\Patient\Services\PatientFeedbackService::class);
    }

    public function boot(): void
    {
        //
    }
}
