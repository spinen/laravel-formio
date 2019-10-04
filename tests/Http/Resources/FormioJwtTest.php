<?php

namespace Spinen\Formio\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Mock;
use Spinen\Formio\TestCase;
use Spinen\Formio\Token;

class FormioJwtTest extends TestCase
{
    /**
     * @var Carbon
     */
    protected $now;

    /**
     * @var Mock
     */
    protected $request_mock;

    /**
     * @var FormioJwt
     */
    protected $resource;

    /**
     * @var Mock
     */
    protected $token_mock;

    protected function setUp(): void
    {
        $this->request_mock = Mockery::mock(Request::class);

        $this->now = Carbon::now();

        $this->token_mock = Mockery::mock(Token::class);
        $this->token_mock->expires_at = $this->now;
        $this->token_mock->jwt = 'jwt here';

        $this->resource = new FormioJwt($this->token_mock);
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(FormioJwt::class, $this->resource);
    }

    /**
     * @test
     */
    public function it_returns_an_array()
    {
        $this->assertTrue(is_array($this->resource->toArray($this->request_mock)));
    }

    /**
     * @test
     */
    public function it_has_jwt_in_response()
    {
        $response = $this->resource->toArray($this->request_mock);

        $this->assertArrayHasKey('jwt', $response);
        $this->assertEquals('jwt here', $response['jwt']);
    }

    /**
     * @test
     */
    public function it_has_expires_at_in_response()
    {
        $response = $this->resource->toArray($this->request_mock);

        $this->assertArrayHasKey('expires_at', $response);
        $this->assertEquals($this->now->toIso8601String(), $response['expires_at']);
    }
}
