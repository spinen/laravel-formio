<?php

namespace Spinen\Formio;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use stdClass;

/**
 * Class Token
 *
 * @package Spinen\Formio
 */
class Token
{
    /**
     * Carbon instance of when token expires
     *
     * @var Carbon
     */
    public $expires_at;

    /**
     * Carbon instance of when token issued
     *
     * @var Carbon
     */
    public $issued_at;

    /**
     * The JWT
     *
     * @var string
     */
    public $jwt;

    /**
     * Parsed JWT as an object
     *
     * @var stdClass
     */
    public $jwt_obj;

    /**
     * Formio User
     *
     * @var array
     */
    public $user;

    /**
     * Is the token expired?
     *
     * @return bool
     */
    public function expired()
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
     *
     * @param string $project
     * @param string $form
     * @param array $user
     * @param array $roles
     * @param string $secret
     * @param string $algorithm
     *
     * @return Token
     */
    public function makeJwt($project, $form, array $user, array $roles, $secret, $algorithm)
    {
        $now = Carbon::now();

        $jwt = [
            'external' => true,
            'form'     => [
                '_id' => $form,
            ],
            'user'     => [
                '_id'   => 'external',
                'data'  => $user,
                'roles' => $roles,
            ],
            'iat'      => $now->timestamp,
            // TODO: Use the same timeout as the docker container
            'exp'      => $now->addMinutes(240)->timestamp,
        ];

        // NOTE: Appears to only be used by enterprise version where you can have multiple "projects"
        if (!is_null($project)) {
            $jwt['project'] = [
                '_id' => $project,
            ];
        }

        return $this->setJwt(JWT::encode($jwt, $secret, $algorithm), $secret, $algorithm)
                    ->setUser($user);
    }

    /**
     * Set the JWT
     *
     * @param string $jwt
     * @param string $secret
     * @param string $algorithm
     *
     * @return $this
     */
    public function setJwt($jwt, $secret, $algorithm)
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
     *
     * @param array $user
     *
     * @return Token
     */
    public function setUser(array $user)
    {
        $this->user = $user;

        return $this;
    }
}
