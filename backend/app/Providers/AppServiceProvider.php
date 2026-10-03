<?php

namespace App\Providers;

use App\Models\Property;
use App\Observers\PropertyObserver;
use App\Services\AI\AiSettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // إعدادات المساعد — singleton خفيف مع كاش داخلي.
        $this->app->singleton(AiSettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // مزامنة فهرس البحث الذكي مع كل تغيير على العقارات.
        Property::observe(PropertyObserver::class);
    }
}
