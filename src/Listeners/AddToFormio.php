<?php

namespace Spinen\Formio\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Spinen\Formio\Client as Formio;
use Spinen\Formio\Exceptions\UserException;

class AddToFormio implements ShouldQueue
{
    /**
     * Formio client instance
     *
     * @var Formio
     */
    protected $formio;

    /**
     * Create the event listener.
     *
     * @param Formio $formio
     */
    public function __construct(Formio $formio)
    {
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
        if (Config::get('formio.user.sync')) {
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
        return Config::get('formio.user.sync');
    }
}
