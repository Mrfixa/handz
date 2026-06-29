<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Re-hydrate APP_MODE into the process env. Once `php artisan config:cache`
        // runs, the .env file is no longer loaded, so the 100+ legacy `env('APP_MODE')`
        // call sites would return null — which, e.g., forces trip/parcel OTPs to the
        // demo value '0000'. Sourcing it from cached config keeps every env('APP_MODE')
        // correct in both cached and non-cached deployments.
        $appMode = config('app.app_mode', 'live');
        putenv("APP_MODE={$appMode}");
        $_ENV['APP_MODE'] = $appMode;
        $_SERVER['APP_MODE'] = $appMode;
    }

    public function boot()
    {
        if($this->app->environment('live')) {
            URL::forceScheme('https');
        }
        Paginator::useBootstrap();
    }
}
