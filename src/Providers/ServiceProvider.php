<?php

namespace Spinen\Formio\Providers;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Spinen\Formio\Client;
use Spinen\Formio\Client as Formio;

/**
 * Class ServiceProvider
 *
 * @package Spinen\Formio\Providers
 */
class ServiceProvider extends LaravelServiceProvider implements DeferrableProvider
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

        $this->registerClient();

        $this->app->alias(Formio::class, 'Formio');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Client::class,
        ];
    }

    /**
     * Register the client
     *
     * If the Formio id or roles are null, then assume sensible values via the API
     */
    protected function registerClient(): void
    {
        $this->app->bind(
            Formio::class,
            function (Application $app) {
                $formio = new Formio(Config::get('formio'), $app->make(Guzzle::class));

                $resourceIds = function () use ($formio) {
                    $id = $formio->login()
                                 ->request('form?name=user')[0]['_id'];

                    $formio->logout();

                    return $id;
                };

                // If the formio id is null, then get it or a cached value for the user resource
                if (empty(Config::get('formio.user.form'))) {
                    Config::set(
                        'formio.user.form',
                        Cache::remember(
                            'formio.id',
                            Carbon::now()
                                  ->addDay(),
                            $resourceIds
                        )
                    );

                    $formio->setConfigs(Config::get('formio'));
                }

                $roleIds = function () use ($formio) {
                    $roles = (array)$formio->login()
                                           ->request('role?title=Authenticated')[0]['_id'];

                    $formio->logout();

                    return $roles;
                };

                // If the user roles are null, then get it or a cached value for authenticated user
                if (empty(Config::get('formio.user.roles'))) {
                    Config::set(
                        'formio.user.roles',
                        Cache::remember(
                            'formio.user.roles',
                            Carbon::now()
                                  ->addDay(),
                            $roleIds
                        )
                    );

                    $formio->setConfigs(Config::get('formio'));
                }

                return $formio;
            }
        );
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
