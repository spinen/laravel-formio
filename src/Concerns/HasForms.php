<?php

namespace Spinen\Formio\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

/**
 * Trait HasForms
 *
 * @property string $formio_password
 */
trait HasForms
{
    /**
     * Accessor for FormioPassword.
     */
    public function getFormioPasswordAttribute(): ?string
    {
        return is_null($this->attributes['formio_password'] ?? null)
            ? null
            : $this->resolveEncrypter()
                   ->decrypt($this->attributes['formio_password']);
    }

    /**
     * Build the array to login to Formio
     */
    public function getLoginData(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->formio_password,
        ];
    }

    /**
     * Build the array to register a user to Formio
     */
    public function getRegistrationData(): array
    {
        return [
            'email' => $this->email,
            'firstName' => $this->first_name,
            'lastName' => $this->last_name,
            'password' => $this->formio_password,
        ];
    }

    /**
     * Make sure that the formio_password is fillable & protected
     */
    public function initializeHasFormsTrait(): void
    {
        $this->fillable[] = 'formio_password';
        $this->hidden[] = 'formio_password';
    }

    /**
     * Resolve the encrypter from the IoC
     *
     * We are staying away from the Crypt facade, so that we can support PHP 7.4 with Laravel 5.x
     *
     * TODO: Remove this when dropping support of Laravel 5.5
     */
    protected function resolveEncrypter(): Encrypter
    {
        return Container::getInstance()
                        ->make(Encrypter::class);
    }

    /**
     * Mutator for FormioPassword.
     */
    public function setFormioPasswordAttribute(?string $formio_password): void
    {
        $this->attributes['formio_password'] = is_null($formio_password)
            ? null
            : $this->resolveEncrypter()
                   ->encrypt($formio_password);
    }
}
