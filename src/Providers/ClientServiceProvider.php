<?php

namespace Spinen\Formio\Providers;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Spinen\Formio\Client as Formio;

/**
 * Class ClientServiceProvider
 *
 * Since this is deferred, it only needed to deal with code that has to do with the client.
 */
class ClientServiceProvider extends LaravelServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
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
            Formio::class,
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
                    $roles = (array) $formio->login()
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
}
