<?php

namespace App\Providers;

use App\Services\AISettingsService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(AISettingsService $settingsService): void
    {
        // Seed default AI settings only if table exists and no records yet
        if (Schema::hasTable('ai_settings')) {
            $settingsService->seedDefaultProviders();
        }
    }
}
