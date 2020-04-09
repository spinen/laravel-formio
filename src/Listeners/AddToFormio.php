<?php

namespace Spinen\Formio\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spinen\Formio\Client as Formio;
use Spinen\Formio\Exceptions\UserException;

class AddToFormio implements ShouldQueue
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * Formio client instance
     *
     * @var Formio
     */
    protected $formio;

    /**
     * Create the event listener.
     *
     * @param Config $config
     * @param Formio $formio
     */
    public function __construct(Config $config, Formio $formio)
    {
        $this->config = $config;
        $this->formio = $formio;
    }

    /**
     * Handle the event.
     *
     * @param Registered $event
     *
     * @return void
     * @throws UserException
     */
    public function handle(Registered $event)
    {
        if ($this->config->get('formio.user.sync')) {
            $this->formio->addUser($event->user);
        }
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param Registered $event
     *
     * @return bool
     */
    public function shouldQueue(Registered $event)
    {
        return $this->config->get('formio.user.sync');
    }
}
