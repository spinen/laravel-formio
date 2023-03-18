<?php

namespace Spinen\Formio\Concerns;

use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\Encrypter;
use Mockery;
use Mockery\Mock;
use ReflectionClass;
use Spinen\Formio\Concerns\Stubs\User;
use Spinen\Formio\TestCase;

class HasFormsTest extends TestCase
{
    /**
     * @var Mock
     */
    protected $encrypter_mock;

    /**
     * @var User
     */
    protected $trait;

    protected function setUp(): void
    {
        $this->trait = new User();

        $this->encrypter_mock = Mockery::mock(Encrypter::class);

        Container::getInstance()
                 ->instance(Encrypter::class, $this->encrypter_mock);
    }

    /**
     * @test
     */
    public function it_can_be_used()
    {
        $this->assertArrayHasKey(HasForms::class, (new ReflectionClass($this->trait))->getTraits());
    }

    /**
     * @test
     */
    public function it_set_fillable_and_hidden_to_have_formio_password()
    {
        $this->assertFalse(in_array('formio_password', $this->trait->fillable), 'Baseline fillable');
        $this->assertFalse(in_array('formio_password', $this->trait->hidden), 'Baseline hidden');

        $this->trait->initializeHasFormsTrait();

        $this->assertTrue(in_array('formio_password', $this->trait->fillable), 'Set fillable');
        $this->assertTrue(in_array('formio_password', $this->trait->hidden), 'Set hidden');
    }

    /**
     * @test
     */
    public function it_encrypts_formio_password()
    {
        $this->encrypter_mock->shouldReceive('encrypt')
                             ->once()
                             ->withArgs(['password'])
                             ->andReturn('encrypted password');

        $this->trait->setFormioPasswordAttribute('password');

        $this->assertEquals('encrypted password', $this->trait->attributes['formio_password']);
    }

    /**
     * @test
     */
    public function it_does_not_encrypt_a_null_formio_password()
    {
        $this->encrypter_mock->shouldReceive('encrypt')
                             ->never()
                             ->withAnyArgs();

        $this->trait->setFormioPasswordAttribute(null);

        $this->assertNull($this->trait->attributes['formio_password']);
    }

    /**
     * @test
     */
    public function it_dencrypts_formio_password()
    {
        $this->encrypter_mock->shouldReceive('decrypt')
                             ->once()
                             ->withArgs(['encrypted password'])
                             ->andReturn('password');

        $this->trait->attributes['formio_password'] = 'encrypted password';

        $this->assertEquals('password', $this->trait->getFormioPasswordAttribute());
    }

    /**
     * @test
     */
    public function it_does_not_dencrypt_a_null_formio_password()
    {
        $this->encrypter_mock->shouldReceive('decrypt')
                             ->never()
                             ->withAnyArgs();

        $this->trait->attributes['formio_password'] = null;

        $this->assertNull($this->trait->getFormioPasswordAttribute());
    }

    /**
     * @test
     */
    public function it_gets_the_expected_login_data()
    {
        $this->trait->email = 'someone@somewhere.com';
        $this->trait->formio_password = 'password';

        $this->assertEquals(
            [
                'email' => 'someone@somewhere.com',
                'password' => 'password',
            ],
            $this->trait->getLoginData()
        );
    }

    /**
     * @test
     */
    public function it_gets_the_expected_registration_data()
    {
        $this->trait->email = 'someone@somewhere.com';
        $this->trait->first_name = 'First';
        $this->trait->last_name = 'Last';
        $this->trait->email = 'someone@somewhere.com';
        $this->trait->formio_password = 'password';

        $this->assertEquals(
            [
                'email' => 'someone@somewhere.com',
                'firstName' => 'First',
                'lastName' => 'Last',
                'password' => 'password',
            ],
            $this->trait->getRegistrationData()
        );
    }
}
