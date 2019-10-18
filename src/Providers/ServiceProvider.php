<?php

namespace Spinen\Formio\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

/**
 * Class ServiceProvider
 *
 * @package Spinen\Formio\Providers
 */
class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishes();

        $this->registerRoutes();
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/formio.php', 'formio');
    }

    /**
     * There are several resources that get published
     *
     * Only worry about telling the application about them if running in the console.
     */
    protected function registerPublishes()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

            $this->publishes(
                [
                    __DIR__ . '/../../config/formio.php' => config_path('formio.php'),
                ],
                'formio-config'
            );

            $this->publishes(
                [
                    __DIR__ . '/../../database/migrations' => database_path('migrations'),
                ],
                'formio-migrations'
            );
        }
    }

    /**
     * Register the routes needed for the registration flow
     */
    protected function registerRoutes()
    {
        if (Config::get('formio.route.enabled')) {
            Route::group(
                [
                    'namespace'  => 'Spinen\Formio\Http\Controllers',
                    'middleware' => Config::get('formio.route.middleware', ['api', 'auth:api']),
                ],
                function () {
                    $this->loadRoutesFrom(realpath(__DIR__ . '/../../routes/web.php'));
                }
            );
        }
    }
}
