<?php

namespace Modules\CRM\Providers;

use Illuminate\Support\ServiceProvider;

class CRMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\CRM\Services\CallCenterService::class);
        $this->app->singleton(\Modules\CRM\Services\CampaignService::class);
        $this->app->singleton(\Modules\CRM\Services\CampaignCostService::class);
        $this->app->singleton(\Modules\CRM\Services\MetaAdsService::class);
        $this->app->singleton(\Modules\CRM\Services\WebhookService::class);
    }

    public function boot(): void
    {
        //
    }
}
