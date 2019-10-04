<?php

namespace Spinen\Formio\Concerns;

use Illuminate\Support\Facades\Crypt;

/**
 * Trait HasForms
 *
 * @package Spinen\Formio
 *
 * @property string $formio_password
 */
trait HasForms
{
    /**
     * Accessor for FormioPassword.
     *
     * @return string
     */
    public function getFormioPasswordAttribute()
    {
        return is_null($this->attributes['formio_password'] ?? null)
            ? null
            : Crypt::decrypt($this->attributes['formio_password']);
    }

    /**
     * Build the array to login to Formio
     *
     * @return array
     */
    public function getLoginData()
    {
        return [
            'email'    => $this->email,
            'password' => $this->formio_password,
        ];
    }

    /**
     * Build the array to register a user to Formio
     *
     * @return array
     */
    public function getRegistrationData()
    {
        return [
            'email'     => $this->email,
            'firstName' => $this->first_name,
            'lastName'  => $this->last_name,
            'password'  => $this->formio_password,
        ];
    }

    /**
     * Make sure that the formio_password is fillable & protected
     */
    public function initializeHasFormsTrait()
    {
        $this->fillable[] = 'formio_password';
        $this->hidden[] = 'formio_password';
    }

    /**
     * Mutator for FormioPassword.
     *
     * @param string $formio_password
     */
    public function setFormioPasswordAttribute($formio_password)
    {
        $this->attributes['formio_password'] = is_null($formio_password) ? null : Crypt::encrypt($formio_password);
    }
}
