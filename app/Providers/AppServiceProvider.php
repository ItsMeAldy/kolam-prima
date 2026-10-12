<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
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
    public function boot(): void
    {
        // Bagikan pengaturan situs ke semua view (dipakai layout, error, dan section).
        View::composer('*', function ($view) {
            if (! isset($view->getData()['settings'])) {
                $view->with('settings', SiteSetting::current());
            }
        });
    }
}
