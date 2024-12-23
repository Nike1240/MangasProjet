<?php

namespace App\Providers;
use App\Services\DKeyConsumptionService;
use App\Services\FreeAccessService;
use Illuminate\Support\ServiceProvider;
use FedaPay\FedaPay;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
<<<<<<< HEAD
        
    }
    public function boot()
    {
    
=======
        $this->app->singleton(FreeAccessService::class);
        $this->app->singleton(DKeyConsumptionService::class);
        //
>>>>>>> 63c78c9ab80a924a0181f9bda66fe2f6841a8b2e
    }

   
}
