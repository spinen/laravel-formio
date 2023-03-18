<?php

namespace Spinen\Formio\Contracts;

/**
 * Interface FormioUser
 *
 * @property $formio_password
 */
interface FormioUser
{
    /**
     * Build the array to login to Formio
     *
     * Generally, an email & password
     */
    public function getLoginData(): array;

    /**
     * Build the array to register a user to Formio
     */
    public function getRegistrationData(): array;

    /**
     * Persist the user with the password for Formio
     */
    public function save(): bool;
}
