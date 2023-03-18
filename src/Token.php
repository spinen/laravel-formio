<?php

namespace Spinen\Formio;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use stdClass;

/**
 * Class Token
 */
class Token
{
    /**
     * Carbon instance of when token expires
     */
    public ?Carbon $expires_at = null;

    /**
     * Carbon instance of when token issued
     */
    public ?Carbon $issued_at = null;

    /**
     * The JWT
     */
    public ?string $jwt = null;

    /**
     * Parsed JWT as an object
     */
    public ?stdClass $jwt_obj = null;

    /**
     * Formio User
     */
    public ?array $user = null;

    /**
     * Is the token expired?
     */
    public function expired(): bool
    {
        return empty($this->expires_at)
            ? true
            : Carbon::now()
                    ->gte($this->expires_at);
    }

    /**
     * Build SSO JWT for a User
     *
     * @see https://help.form.io/integrations/sso/
     */
    public function makeJwt(
        ?string $project,
        string $form,
        array $user,
        array $roles,
        string $secret,
        string $algorithm
    ): Token {
        $now = Carbon::now();

        $jwt = [
            'external' => true,
            'form' => [
                '_id' => $form,
            ],
            'user' => [
                '_id' => 'external',
                'data' => $user,
                'roles' => $roles,
            ],
            'iat' => $now->timestamp,
            // TODO: Use the same timeout as the docker container
            'exp' => $now->addMinutes(240)->timestamp,
        ];

        // NOTE: Appears to only be used by enterprise version where you can have multiple "projects"
        if (! is_null($project)) {
            $jwt['project'] = [
                '_id' => $project,
            ];
        }

        return $this->setJwt(
            algorithm: $algorithm,
            jwt: JWT::encode($jwt, $secret, $algorithm),
            secret: $secret,
        )->setUser($user);
    }

    /**
     * Set the JWT
     */
    public function setJwt(string $jwt, string $secret, string $algorithm): self
    {
        // 1 second buffer to time difference
        JWT::$leeway += 10;

        $this->jwt = $jwt;
        $this->jwt_obj = JWT::decode($this->jwt, $secret, [$algorithm]);

        $this->expires_at = Carbon::createFromTimestamp($this->jwt_obj->exp);
        $this->issued_at = Carbon::createFromTimestamp($this->jwt_obj->iat);

        return $this;
    }

    /**
     * Set the User
     */
    public function setUser(array $user): self
    {
        $this->user = $user;

        return $this;
    }
}
