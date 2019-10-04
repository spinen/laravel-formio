<?php

namespace Spinen\Formio;

use Exception;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Spinen\Formio\Contracts\FormioUser;
use Spinen\Formio\Exceptions\TokenException;
use Spinen\Formio\Exceptions\UserException;

/**
 * Class Client
 *
 * @package Spinen\Formio
 */
class Client
{
    /**
     * Configs for the client
     *
     * @var array
     */
    protected $configs;

    /**
     * Guzzle instance
     *
     * @var Guzzle
     */
    protected $guzzle;

    /**
     * Token instance
     *
     * @var Token
     */
    public $token;

    /**
     * Client constructor.
     *
     * @param array $configs
     * @param Guzzle $guzzle
     * @param Token|null $token
     */
    public function __construct(array $configs, Guzzle $guzzle, Token $token = null)
    {
        $this->setConfigs($configs);
        $this->guzzle = $guzzle;
        $this->token = $token ?? new Token();
    }

    /**
     * Add a user to Formio
     *
     * @param FormioUser $user
     * @param null $password
     *
     * @return Client
     * @throws UserException
     */
    public function addUser(FormioUser $user, $password = null)
    {
        $user->formio_password = $password ?? $this->configs['user']['register']['default_password'] ?? Str::random(32);

        try {
            $response = $this->guzzle->post(
                $this->uri($this->configs['user']['register']['path']),
                [
                    'form_params' => [
                        'data' => $user->getRegistrationData(),
                    ],
                ]
            );

            if (!$user->save()) {
                throw new UserException("Unable to save the user's Formio password");

                // TODO: Rollback
            }

            $this->parseUser($response);

            return $this;
        } catch (RequestException $e) {
            // TODO: Figure out what to do with this error

            throw $e;
        }
    }

    /**
     * Build admin login
     *
     * @return array
     */
    protected function getAdminLoginData()
    {
        return [
            'email'    => $this->configs['admin']['username'],
            'password' => $this->configs['admin']['password'],
        ];
    }

    /**
     * Login user to Formio
     *
     * If no user provided, then use the admin user
     *
     * @param FormioUser|null $user
     *
     * @return Client
     * @throws Exception
     */
    public function login(FormioUser $user = null)
    {
        try {
            $this->parseUser(
                $this->guzzle->post(
                    $this->uri(
                        $user ? $this->configs['user']['login']['path'] : $this->configs['admin']['login']['path']
                    ),
                    [
                        'form_params' => [
                            'data' => $user ? $user->getLoginData() : $this->getAdminLoginData(),
                        ],
                    ]
                )
            );

            return $this;
        } catch (RequestException $e) {
            // TODO: Figure out what to do with this error

            throw $e;
        }
    }

    /**
     * Logout
     *
     * Since the Formio is stateless, just empty the Token
     *
     * @return $this
     */
    public function logout()
    {
        $this->token = new Token();

        return $this;
    }

    /**
     * Parse the user & JWT from the response into the token
     *
     * @param ResponseInterface $response
     */
    protected function parseUser(ResponseInterface $response)
    {
        // TODO: Add some error checking to user parsing
        $this->token = $this->token->setUser(json_decode($response->getBody(), true))
                                   ->setJwt(
                                       $response->getHeader('x-jwt-token')[0],
                                       $this->configs['jwt']['secret'],
                                       $this->configs['jwt']['algorithm']
                                   );
    }

    /**
     * Make an API call to Formio
     *
     * @param $path
     * @param array|null $data
     * @param string|null $method
     *
     * @return array
     * @throws GuzzleException
     * @throws TokenException
     */
    public function request($path, $data = [], $method = 'GET')
    {
        if (!$this->token) {
            throw new TokenException('Must be logged in before making a request');
        }

        if ($this->token->expired()) {
            throw new TokenException('Token expired ' . $this->token->expires_at->diffForHumans());
        }

        try {
            $response = $this->guzzle->request(
                $method,
                $this->uri($path),
                [
                    'headers'     => [
                        'x-jwt-token' => $this->token->jwt,
                    ],
                    'form_params' => [
                        'data' => $data,
                    ],
                ]
            );

            return json_decode($response->getBody(), true);
        } catch (GuzzleException $e) {
            // TODO: Figure out what to do with this error

            throw $e;
        }
    }

    /**
     * Set the configs
     *
     * @param array $configs
     *
     * @return $this
     */
    public function setConfigs(array $configs)
    {
        $this->configs = $configs;

        return $this;
    }

    /**
     * SSO for User
     *
     * If the user already has a Formio password, then login the use.
     * Otherwise, make a Custom JWT.
     *
     * @param FormioUser $user
     *
     * @return Client
     * @throws Exception
     */
    public function sso(FormioUser $user)
    {
        // If the user has a Formio password, then log them in
        if ($user->formio_password) {
            return $this->login($user);
        }

        $this->token = $this->token->makeJwt(
            $this->configs['project']['id'],
            $this->configs['user']['form'],
            Arr::except($user->getRegistrationData(), 'password'),
            // TODO: Roles from the database?
            $this->configs['user']['roles'],
            $this->configs['jwt']['secret'],
            $this->configs['jwt']['algorithm']
        );

        return $this;
    }

    /**
     * URL to Formio
     *
     * If path is passed in, then append it to the end
     *
     * @param null $path
     *
     * @return string
     */
    public function uri($path = null)
    {
        return rtrim($this->configs['url'], '/') . '/' . ltrim($path, '/');
    }
}
