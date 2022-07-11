<?php
namespace Spork\Development;

use App\Spork;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class DevelopmentServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        Spork::addFeature('Development', 'CodeIcon', '/dev', 'tool');
        if (config('spork.development.enabled')) {
            Route::middleware($this->app->make('config')->get('spork.development.middleware', ['web', 'auth:sanctum']))
                ->prefix('api')
                ->group(__DIR__ . '/../routes/api.php');
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