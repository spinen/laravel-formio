<?php

namespace Spinen\Formio\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Config\Repository as Config;
use Mockery;
use Mockery\Mock;
use Spinen\Formio\Client;
use Spinen\Formio\Contracts\FormioUser;
use Spinen\Formio\TestCase;

class AddToFormioTest extends TestCase
{
    /**
     * @var Mock
     */
    protected $client_mock;

    /**
     * @var Mock
     */
    protected $config_mock;

    /**
     * @var AddToFormio
     */
    protected $listener;

    /**
     * @var Mock
     */
    protected $registered_mock;

    /**
     * @var Mock
     */
    protected $user_mock;

    protected function setUp(): void
    {
        $this->client_mock = Mockery::mock(Client::class);
        $this->config_mock = Mockery::mock(Config::class);
        $this->registered_mock = Mockery::mock(Registered::class);
        $this->user_mock = Mockery::mock(FormioUser::class);

        $this->registered_mock->user = $this->user_mock;

        $this->listener = new AddToFormio($this->config_mock, $this->client_mock);
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(AddToFormio::class, $this->listener);
    }

    /**
     * @test
     */
    public function it_adds_user_to_formio_when_sync_is_configured_true()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('formio.user.sync')
                          ->andReturnTrue();

        $this->client_mock->shouldReceive('addUser')
                          ->once()
                          ->with($this->user_mock)
                          ->andReturnNull();

        $this->listener->handle($this->registered_mock);
    }

    /**
     * @test
     */
    public function it_does_not_adds_user_to_formio_when_sync_is_configured_false()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('formio.user.sync')
                          ->andReturnFalse();

        $this->client_mock->shouldReceive('addUser')
                          ->never()
                          ->withAnyArgs();

        $this->listener->handle($this->registered_mock);
    }

    /**
     * @test
     */
    public function it_will_queue_job_if_sync_is_configured_true()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('formio.user.sync')
                          ->andReturnTrue();

        $this->assertTrue($this->listener->shouldQueue($this->registered_mock));
    }

    /**
     * @test
     */
    public function it_will_not_queue_job_if_sync_is_configured_false()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('formio.user.sync')
                          ->andReturnFalse();

        $this->assertFalse($this->listener->shouldQueue($this->registered_mock));
    }
}
