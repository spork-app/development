<?php

namespace Spork\Development;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spork\Core\Spork;

class DevelopmentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Spork::fabricateWith(__DIR__.'/../resources/Development/parts');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Spork::addFeature('Development', 'CodeIcon', '/dev', 'tool', ['development']);
        if (config('spork.development.enabled')) {
            Route::middleware($this->app->make('config')->get('spork.development.middleware', ['web', 'auth:sanctum']))
                ->prefix('api')
                ->group(__DIR__.'/../routes/api.php');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
        // return ['spork.development'];
    }
}
