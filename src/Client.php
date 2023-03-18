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
 */
class Client
{
    /**
     * The Token instance
     */
    public ?Token $token = null;

    /**
     * Client constructor.
     */
    public function __construct(
        protected array $configs,
        protected Guzzle $guzzle,
        ?Token $token = null
    ) {
        $this->setConfigs($configs);
        $this->token = $token ?? new Token();
    }

    /**
     * Add a user to Formio
     *
     * @throws UserException
     */
    public function addUser(FormioUser $user, ?string $password = null): self
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

            if (! $user->save()) {
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
     */
    protected function getAdminLoginData(): array
    {
        return [
            'email' => $this->configs['admin']['username'],
            'password' => $this->configs['admin']['password'],
        ];
    }

    /**
     * Login user to Formio
     *
     * If no user provided, then use the admin user
     *
     * @throws Exception
     */
    public function login(FormioUser $user = null): Client
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
     */
    public function logout(): self
    {
        $this->token = new Token();

        return $this;
    }

    /**
     * Parse the user & JWT from the response into the token
     */
    protected function parseUser(ResponseInterface $response): void
    {
        // TODO: Add some error checking to user parsing
        $this->token = $this->token->setUser(json_decode($response->getBody(), true))
            ->setJwt(
                algorithm: $this->configs['jwt']['algorithm'],
                jwt: $response->getHeader('x-jwt-token')[0],
                secret: $this->configs['jwt']['secret'],
            );
    }

    /**
     * Make an API call to Formio
     *
     * @throws GuzzleException
     * @throws TokenException
     */
    public function request(string $path, ?array $data = [], ?string $method = 'GET'): array
    {
        if (! $this->token) {
            throw new TokenException('Must be logged in before making a request');
        }

        if ($this->token->expired()) {
            throw new TokenException('Token expired '.$this->token->expires_at->diffForHumans());
        }

        try {
            $response = $this->guzzle->request(
                $method,
                $this->uri($path),
                [
                    'headers' => [
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
     */
    public function setConfigs(array $configs): self
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
     * @throws Exception
     */
    public function sso(FormioUser $user): Client
    {
        // If the user has a Formio password, then log them in
        if ($user->formio_password) {
            return $this->login($user);
        }

        $this->token = $this->token->makeJwt(
            algorithm: $this->configs['jwt']['algorithm'],
            form: $this->configs['user']['form'],
            project: $this->configs['project']['id'] ?? null,
            // TODO: Roles from the database?
            roles: $this->configs['user']['roles'],
            secret: $this->configs['jwt']['secret'],
            user: Arr::except($user->getRegistrationData(), 'password'),
        );

        return $this;
    }

    /**
     * URL to Formio
     *
     * If path is passed in, then append it to the end
     */
    public function uri(?string $path = null): string
    {
        return rtrim($this->configs['url'], '/').'/'.ltrim($path, '/');
    }
}
