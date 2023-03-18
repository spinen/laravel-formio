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
     * Create the event listener.
     */
    public function __construct(protected Config $config, protected Formio $formio)
    {
    }

    /**
     * Handle the event.
     *
     * @throws UserException
     */
    public function handle(Registered $event): void
    {
        if ($this->config->get('formio.user.sync')) {
            $this->formio->addUser($event->user);
        }
    }

    /**
     * Determine whether the listener should be queued.
     */
    public function shouldQueue(Registered $event): bool
    {
        return $this->config->get('formio.user.sync');
    }
}
