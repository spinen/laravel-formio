<?php

namespace Spinen\Formio\Contracts;

/**
 * Interface FormioUser
 *
 * @package Spinen\Formio
 *
 * @property $formio_password
 */
interface FormioUser
{
    /**
     * Build the array to login to Formio
     *
     * Generally, an email & password
     *
     * @return array
     */
    public function getLoginData();

    /**
     * Build the array to register a user to Formio
     *
     * @return array
     */
    public function getRegistrationData();

    /**
     * Persist the user with the password for Formio
     *
     * @return bool
     */
    public function save();
}
