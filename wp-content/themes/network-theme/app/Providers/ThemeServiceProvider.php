<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
        
        $this->app->register(\BladeUI\Icons\BladeIconsServiceProvider::class);
        $this->app->register(\BladeUI\Icons\BladeHeroiconsServiceProvider::class);
        $this->app->register(\App\Providers\StoreFieldsServiceProvider::class);
        $this->app->register(\App\Providers\SchemaServiceProvider::class);
        $this->app->register(\App\Providers\DeliveryRulesServiceProvider::class);
        $this->app->register(\App\Providers\NetworkFieldsServiceProvider::class);
        $this->app->register(\App\Providers\ProductSeoServiceProvider::class);
        $this->app->register(\App\Providers\SitemapServiceProvider::class);
        $this->app->register(\App\Providers\GoogleBusinessServiceProvider::class);
        $this->app->register(\App\Providers\GrowthDashboardServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
