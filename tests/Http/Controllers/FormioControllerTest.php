<?php

namespace Spinen\Formio\Http\Controllers;

use Illuminate\Http\Request;
use Mockery;
use Mockery\Mock;
use Spinen\Formio\Client;
use Spinen\Formio\Contracts\FormioUser;
use Spinen\Formio\Http\Resources\FormioJwt;
use Spinen\Formio\TestCase;
use Spinen\Formio\Token;

class FormioControllerTest extends TestCase
{
    /**
     * @var Mock
     */
    protected $client_mock;

    /**
     * @var FormioController
     */
    protected $controller;

    /**
     * @var Mock
     */
    protected $request_mock;

    /**
     * @var Mock
     */
    protected $user_mock;

    protected function setUp(): void
    {
        $this->client_mock = Mockery::mock(Client::class);

        $this->request_mock = Mockery::mock(Request::class);

        $this->user_mock = Mockery::mock(FormioUser::class);

        $this->controller = new FormioController();
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(FormioController::class, $this->controller);
    }

    /**
     * @test
     */
    public function it_returns_expected_response()
    {
        $this->request_mock->shouldReceive('user')
                           ->once()
                           ->withNoArgs()
                           ->andReturn($this->user_mock);

        $this->client_mock->shouldReceive('sso')
                          ->once()
                          ->withAnyArgs(
                              [
                                  $this->user_mock,
                              ]
                          )
                          ->andReturnSelf();

        $this->assertInstanceOf(FormioJwt::class, $this->controller->jwt($this->request_mock, $this->client_mock));
    }

    /**
     * @test
     */
    public function it_has_token_in_response()
    {
        $this->request_mock->shouldReceive('user')
                           ->once()
                           ->withNoArgs()
                           ->andReturn($this->user_mock);

        $this->client_mock->shouldReceive('sso')
                          ->once()
                          ->withAnyArgs(
                              [
                                  $this->user_mock,
                              ]
                          )
                          ->andReturnSelf();

        $this->client_mock->token = new Token();

        $this->assertInstanceOf(
            Token::class,
            $this->controller->jwt($this->request_mock, $this->client_mock)->resource
        );
    }
}
