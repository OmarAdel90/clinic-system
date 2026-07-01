<?php

namespace Modules\Transaction\Providers;

use Illuminate\Support\ServiceProvider;

class TransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\Modules\Transaction\Services\TransactionService::class);
    }

    public function boot(): void
    {
        //
    }
}
