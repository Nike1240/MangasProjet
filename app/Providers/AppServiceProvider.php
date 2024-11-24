<?php

namespace App\Providers;
use App\Services\DKeyConsumptionService;
use App\Services\FreeAccessService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FreeAccessService::class);
        $this->app->singleton(DKeyConsumptionService::class);
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
