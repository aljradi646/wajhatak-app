<?php

namespace App\Providers;

use App\Models\Property;
use App\Models\PropertyFeature;
use App\Models\PropertyImage;
use App\Models\PropertyLocation;
use App\Observers\PropertyObserver;
use App\Observers\PropertyFeatureObserver;
use App\Observers\PropertyImageObserver;
use App\Observers\PropertyLocationObserver;
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
        PropertyImage::observe(PropertyImageObserver::class);
        PropertyLocation::observe(PropertyLocationObserver::class);
        PropertyFeature::observe(PropertyFeatureObserver::class);
    }
}
