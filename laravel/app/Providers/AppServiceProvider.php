<?php

namespace App\Providers;

use App\Services\HemisIntegrations\HemisApiService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HemisApiService::class, function ($app) {
            $baseUrl = config('services.hemis.base_url');
            $token = config('services.hemis.token');

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    $baseUrl = \App\Models\Setting::get('hemis.base_url', $baseUrl);
                    $token = \App\Models\Setting::get('hemis.token', $token);
                }
            } catch (\Exception $e) {
                // Ignore if DB isn't ready or settings table doesn't exist
            }

            return new HemisApiService(
                baseUrl: $baseUrl ?? '',
                token: $token ?? '',
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Disable Vite's Link header prefetching — the large headers cause
        // 502 errors from the upstream OpenResty proxy (buffer overflow)
        Vite::prefetch(concurrency: 0);

        // Super-admin bypasses all permission checks (standard Spatie pattern)
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
