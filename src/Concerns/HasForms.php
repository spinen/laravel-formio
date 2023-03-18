<?php

namespace Spinen\Formio\Concerns;

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
            : Crypt::decryptString($this->attributes['formio_password']);
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
     * Mutator for FormioPassword.
     */
    public function setFormioPasswordAttribute(?string $formio_password): void
    {
        $this->attributes['formio_password'] = is_null($formio_password)
            ? null
            : Crypt::encryptString($formio_password);
    }
}
