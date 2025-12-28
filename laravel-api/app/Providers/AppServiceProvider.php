<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(\App\Services\CryptoService::class);
        $this->app->singleton(\App\Services\JwtService::class);
        $this->app->singleton(\App\Services\AuditService::class);
        
        $this->app->singleton(\App\Services\ShopierService::class, function ($app) {
            return new \App\Services\ShopierService($app->make(\App\Services\CryptoService::class));
        });
        
        $this->app->singleton(\App\Services\EmailService::class, function ($app) {
            return new \App\Services\EmailService($app->make(\App\Services\CryptoService::class));
        });
        
        $this->app->singleton(\App\Services\RapidApiService::class);
        $this->app->singleton(\App\Services\RiskService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}