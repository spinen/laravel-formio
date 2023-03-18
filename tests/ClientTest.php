<?php

namespace Spinen\Formio;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mockery;
use Mockery\Mock;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Spinen\Formio\Contracts\FormioUser;
use Spinen\Formio\Exceptions\TokenException;
use Spinen\Formio\Exceptions\UserException;

/**
 * Class ClientTest
 */
class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $configs = [];

    /**
     * @var Mock
     */
    protected $guzzle_mock;

    /**
     * @var Mock
     */
    protected $response_mock;

    /**
     * @var Mock
     */
    protected $token_mock;

    /**
     * User
     *
     * @var array
     */
    protected $user;

    /**
     * @var Mock
     */
    protected $user_mock;

    protected function setUp(): void
    {
        $this->configs = require __DIR__.'/../config/formio.php';
        $this->configs['user']['form'] = Str::random();

        $this->guzzle_mock = Mockery::mock(Guzzle::class);

        $this->response_mock = Mockery::mock(ResponseInterface::class);

        $this->token_mock = Mockery::mock(Token::class);

        $this->user = [
            'email' => 'someone@somewhere.com',
            'password' => 'password',
        ];

        $this->user_mock = Mockery::mock(FormioUser::class);

        $this->client = new Client($this->configs, $this->guzzle_mock, $this->token_mock);
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(Client::class, $this->client);
    }

    /**
     * @test
     */
    public function it_can_add_a_user_to_formio()
    {
        $this->user_mock->shouldReceive('getRegistrationData')
                        ->once()
                        ->withNoArgs()
                        ->andReturn($this->user);
        $this->user_mock->shouldReceive('save')
                        ->once()
                        ->withNoArgs()
                        ->andReturnTrue();

        $this->response_mock->shouldReceive('getBody')
                            ->once()
                            ->withNoArgs()
                            ->andReturn(json_encode($this->user));
        $this->response_mock->shouldReceive('getHeader')
                            ->once()
                            ->with('x-jwt-token')
                            ->andReturn(
                                [
                                    'jwt',
                                ]
                            );

        $this->guzzle_mock->shouldReceive('post')
                          ->once()
                          ->withArgs(
                              [
                                  $this->configs['url'].$this->configs['user']['register']['path'],
                                  [
                                      'form_params' => [
                                          'data' => $this->user,
                                      ],
                                  ],
                              ]
                          )
                          ->andReturn($this->response_mock);

        $this->token_mock->shouldReceive('setUser')
                         ->once()
                         ->with($this->user)
                         ->andReturnSelf();
        $this->token_mock->shouldReceive('setJwt')
                         ->once()
                         ->withArgs(
                             [
                                 'jwt',
                                 $this->configs['jwt']['secret'],
                                 $this->configs['jwt']['algorithm'],
                             ]
                         );

        $this->client->addUser($this->user_mock);
    }

    /**
     * @test
     */
    public function it_raises_exception_when_saving_user_fails_while_adding_user()
    {
        $this->expectException(UserException::class);

        $this->user_mock->shouldReceive('getRegistrationData')
                        ->once()
                        ->withNoArgs()
                        ->andReturn($this->user);
        $this->user_mock->shouldReceive('save')
                        ->once()
                        ->withNoArgs()
                        ->andReturnFalse();

        $this->guzzle_mock->shouldReceive('post')
                          ->once()
                          ->withAnyArgs()
                          ->andReturn($this->response_mock);

        $this->client->addUser($this->user_mock);
    }

    /**
     * @test
     */
    public function it_raises_exception_when_posting_user_fails()
    {
        $this->expectException(RequestException::class);

        $this->user_mock->shouldReceive('getRegistrationData')
                        ->once()
                        ->withNoArgs()
                        ->andReturn($this->user);
        $this->user_mock->shouldReceive('save')
                        ->never();

        $request_mock = Mockery::mock(RequestInterface::class);

        $this->guzzle_mock->shouldReceive('post')
                          ->once()
                          ->withAnyArgs()
                          ->andThrow(new RequestException('message', $request_mock));

        $this->client->addUser($this->user_mock);
    }

    /**
     * @test
     */
    public function it_can_log_in_a_user()
    {
        $this->user_mock->shouldReceive('getLoginData')
                        ->once()
                        ->withNoArgs()
                        ->andReturn(
                            [
                                $this->user,
                            ]
                        );

        $this->response_mock->shouldReceive('getBody')
                            ->once()
                            ->withNoArgs()
                            ->andReturn(json_encode($this->user));
        $this->response_mock->shouldReceive('getHeader')
                            ->once()
                            ->with('x-jwt-token')
                            ->andReturn(
                                [
                                    'jwt',
                                ]
                            );

        $this->guzzle_mock->shouldReceive('post')
                          ->once()
                          ->withArgs(
                              [
                                  $this->configs['url'].$this->configs['user']['login']['path'],
                                  [
                                      'form_params' => [
                                          'data' => [$this->user],
                                      ],
                                  ],
                              ]
                          )
                          ->once()
                          ->andReturn($this->response_mock);

        $this->token_mock->shouldReceive('setUser')
                         ->once()
                         ->with($this->user)
                         ->andReturnSelf();
        $this->token_mock->shouldReceive('setJwt')
                         ->once()
                         ->withArgs(
                             [
                                 'jwt',
                                 $this->configs['jwt']['secret'],
                                 $this->configs['jwt']['algorithm'],
                             ]
                         );

        $this->client->login($this->user_mock);
    }

    /**
     * @test
     */
    public function it_can_log_in_an_admin_when_no_user_given()
    {
        $this->response_mock->shouldReceive('getBody')
                            ->once()
                            ->withNoArgs()
                            ->andReturn(json_encode($this->user));
        $this->response_mock->shouldReceive('getHeader')
                            ->once()
                            ->with('x-jwt-token')
                            ->andReturn(
                                [
                                    'jwt',
                                ]
                            );

        $this->guzzle_mock->shouldReceive('post')
                          ->once()
                          ->withArgs(
                              [
                                  $this->configs['url'].$this->configs['admin']['login']['path'],
                                  [
                                      'form_params' => [
                                          'data' => [
                                              'email' => $this->configs['admin']['username'],
                                              'password' => $this->configs['admin']['password'],
                                          ],
                                      ],
                                  ],
                              ]
                          )
                          ->once()
                          ->andReturn($this->response_mock);

        $this->token_mock->shouldReceive('setUser')
                         ->once()
                         ->with($this->user)
                         ->andReturnSelf();
        $this->token_mock->shouldReceive('setJwt')
                         ->once()
                         ->withArgs(
                             [
                                 'jwt',
                                 $this->configs['jwt']['secret'],
                                 $this->configs['jwt']['algorithm'],
                             ]
                         );

        $this->client->login();
    }

    /**
     * @test
     */
    public function it_raises_exception_when_logging_in_a_user_fails()
    {
        $this->expectException(RequestException::class);

        $request_mock = Mockery::mock(RequestInterface::class);

        $this->guzzle_mock->shouldReceive('post')
                          ->once()
                          ->withAnyArgs()
                          ->andThrow(new RequestException('message', $request_mock));

        $this->client->login();
    }

    /**
     * @test
     */
    public function it_logs_out_a_user()
    {
        $this->assertNotSame($this->client->token, $this->client->logout()->token);
    }

    /**
     * @test
     */
    public function it_gets_a_sso_jwt_for_a_synced_user()
    {
        $this->user_mock->formio_password = 'password';
        $this->user_mock->shouldReceive('getLoginData')
                        ->withAnyArgs()
                        ->andReturn(
                            [
                                $this->user,
                            ]
                        );

        $this->response_mock->shouldReceive('getBody')
                            ->withNoArgs()
                            ->andReturn(json_encode($this->user));
        $this->response_mock->shouldReceive('getHeader')
                            ->withAnyArgs()
                            ->andReturn(
                                [
                                    'jwt',
                                ]
                            );

        $this->guzzle_mock->shouldReceive('post')
                          ->withAnyArgs()
                          ->andReturn($this->response_mock);

        $this->token_mock->shouldReceive('setUser')
                         ->with($this->user)
                         ->andReturnSelf();
        $this->token_mock->shouldReceive('setJwt')
                         ->withAnyArgs();

        $this->client->sso($this->user_mock);
    }

    /**
     * @test
     */
    public function it_builds_a_sso_jwt_for_a_nonsynced_user()
    {
        $this->user_mock->formio_password = null;
        $this->user_mock->shouldReceive('getRegistrationData')
                        ->withAnyArgs()
                        ->andReturn($this->user);

        $this->token_mock->shouldReceive('makeJwt')
                         ->once()
                         ->withArgs(
                             [
                                 $this->configs['project']['id'],
                                 $this->configs['user']['form'],
                                 Arr::except($this->user, 'password'),
                                 $this->configs['user']['roles'],
                                 $this->configs['jwt']['secret'],
                                 $this->configs['jwt']['algorithm'],
                             ]
                         )
                         ->andReturnSelf();

        $this->client->sso($this->user_mock);
    }

    /**
     * @test
     */
    public function it_makes_request_to_formio_with_correct_patameters()
    {
        $this->token_mock->shouldReceive('expired')
                         ->once()
                         ->withNoArgs()
                         ->andReturnFalse();
        $this->token_mock->jwt = 'jwt';

        $this->guzzle_mock->shouldReceive('request')
                          ->once()
                          ->withArgs(
                              [
                                  'GET',
                                  $this->configs['url'].'/some/uri',
                                  [
                                      'headers' => [
                                          'x-jwt-token' => 'jwt',
                                      ],
                                      'form_params' => [
                                          'data' => [],
                                      ],
                                  ],
                              ]
                          )
                          ->andReturn($this->response_mock);

        $this->response_mock->shouldReceive('getBody')
                            ->once()
                            ->withNoArgs()
                            ->andReturn(json_encode([]));

        $this->assertEquals([], $this->client->request('/some/uri'));
    }

    /**
     * @test
     */
    public function it_makes_post_data_to_formio_with_correct_patameters()
    {
        $this->token_mock->shouldReceive('expired')
                         ->once()
                         ->withNoArgs()
                         ->andReturnFalse();
        $this->token_mock->jwt = 'jwt';

        $this->guzzle_mock->shouldReceive('request')
                          ->once()
                          ->withArgs(
                              [
                                  'POST',
                                  $this->configs['url'].'/some/uri',
                                  [
                                      'headers' => [
                                          'x-jwt-token' => 'jwt',
                                      ],
                                      'form_params' => [
                                          'data' => ['stuff'],
                                      ],
                                  ],
                              ]
                          )
                          ->andReturn($this->response_mock);

        $this->response_mock->shouldReceive('getBody')
                            ->once()
                            ->withNoArgs()
                            ->andReturn(json_encode([]));

        $this->assertEquals([], $this->client->request('/some/uri', ['stuff'], 'POST'));
    }

    /**
     * @test
     */
    public function it_raises_exception_when_no_token()
    {
        $this->client->token = null;

        $this->expectException(TokenException::class);

        $this->client->request('/some/uri');
    }

    /**
     * @test
     */
    public function it_raises_exception_when_token_expired()
    {
        $this->expectException(TokenException::class);

        $this->token_mock->shouldReceive('expired')
                         ->once()
                         ->withNoArgs()
                         ->andReturnTrue();

        $this->token_mock->expires_at = Carbon::now();

        $this->client->request('/some/uri');
    }

    /**
     * @test
     */
    public function it_raises_exception()
    {
        $this->expectException(RequestException::class);

        $this->token_mock->shouldReceive('expired')
                         ->once()
                         ->withNoArgs()
                         ->andReturnFalse();
        $this->token_mock->jwt = 'jwt';

        $request_mock = Mockery::mock(RequestInterface::class);

        $this->guzzle_mock->shouldReceive('request')
                          ->once()
                          ->withAnyArgs()
                          ->andThrow(new RequestException('message', $request_mock));

        $this->client->request('/some/uri');
    }
}
