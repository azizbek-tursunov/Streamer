<?php

namespace App\Providers;

use App\Services\HemisIntegrations\HemisApiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HemisApiService::class, function ($app) {
            return new HemisApiService(
                baseUrl: config('services.hemis.base_url'),
                token: config('services.hemis.token'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
