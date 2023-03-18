<?php

namespace Spinen\Formio;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use stdClass;

/**
 * Class TokenTest
 */
class TokenTest extends TestCase
{
    /**
     * @var array
     */
    protected $configs = [];

    /**
     * @var token
     */
    protected $token;

    protected function setUp(): void
    {
        $this->configs = require __DIR__.'/../config/formio.php';

        $this->token = new Token();
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(Token::class, $this->token);
    }

    /**
     * @test
     */
    public function it_sets_the_user()
    {
        $this->assertEmpty($this->token->user, 'Baseline');

        $user = [
            'name' => 'Name',
            'email' => 'user@place.com',
        ];

        $this->token->setUser($user);

        $this->assertEquals($user, $this->token->user, 'Set');
    }

    /**
     * @test
     */
    public function it_sets_the_jwt()
    {
        $this->assertNull($this->token->jwt, 'Baseline JWT');
        $this->assertNull($this->token->jwt_obj, 'Baseline JWT Object');
        $this->assertNull($this->token->expires_at, 'Baseline Expires At');
        $this->assertNull($this->token->issued_at, 'Baseline Issued At');
        $this->assertTrue($this->token->expired(), 'Baseline expired');

        $jwt_payload = [
            'user' => [
                'name' => 'Name',
                'email' => 'user@place.com',
            ],
            'iat' => Carbon::now()->timestamp,
            'exp' => Carbon::now()
                            ->addMinutes(240)->timestamp,
        ];

        $jwt = JWT::encode($jwt_payload, $this->configs['jwt']['secret'], $this->configs['jwt']['algorithm']);

        $this->token->setJwt($jwt, $this->configs['jwt']['secret'], $this->configs['jwt']['algorithm']);

        $this->assertEquals($jwt, $this->token->jwt, 'Set JWT');
        $this->assertInstanceOf(stdClass::class, $this->token->jwt_obj, 'Set JWT Object');
        $this->assertNotEmpty($this->token->jwt_obj, 'Set JWT Object');
        $this->assertTrue($this->token->expires_at->gt(Carbon::now()), 'Set Expires At');
        $this->assertTrue(Carbon::now()->gt($this->token->issued_at), 'Set Issues At');
        $this->assertFalse($this->token->expired(), 'Set expired');
    }

    /**
     * @test
     */
    public function it_makes_a_custom_jwt()
    {
        $this->assertNull($this->token->jwt, 'Baseline JWT');
        $this->assertNull($this->token->jwt_obj, 'Baseline JWT Object');
        $this->assertNull($this->token->expires_at, 'Baseline Expires At');
        $this->assertNull($this->token->issued_at, 'Baseline Issued At');
        $this->assertTrue($this->token->expired(), 'Baseline expired');

        $user = [
            'name' => 'Name',
            'email' => 'user@place.com',
        ];

        $this->token->makeJwt(
            null,
            'form.id',
            $user,
            [
                'role 1',
                'role 2',
            ],
            $this->configs['jwt']['secret'],
            $this->configs['jwt']['algorithm']
        );

        $this->assertNotEmpty($this->token->jwt, 'Set JWT');
        $this->assertNotEmpty($this->token->jwt_obj, 'Set JWT Object');
        $this->assertTrue($this->token->expires_at->gt(Carbon::now()), 'Set Expires At');
        $this->assertTrue(Carbon::now()->gt($this->token->issued_at), 'Set Issues At');
        $this->assertFalse($this->token->expired(), 'Set expired');
        $this->assertEquals($user, $this->token->user, 'Set user');
        $this->assertTrue($this->token->jwt_obj->external, 'JWT obj external prop');
        $this->assertTrue($this->token->jwt_obj->external, 'JWT obj external prop');
        $this->assertFalse(property_exists($this->token->jwt_obj, 'project'), 'JWT obj project prop (missing)');
        $this->assertInstanceOf(stdClass::class, $this->token->jwt_obj->form, 'JWT obj form prop');
        $this->assertEquals('form.id', $this->token->jwt_obj->form->_id, 'JWT obj form prop _id prop');
        $this->assertInstanceOf(stdClass::class, $this->token->jwt_obj->user, 'JWT obj user prop');
        $this->assertEquals('external', $this->token->jwt_obj->user->_id, 'JWT obj user prop _id prop');
        $this->assertInstanceOf(stdClass::class, $this->token->jwt_obj->user->data, 'JWT obj user prop data prop');
        $this->assertEquals('Name', $this->token->jwt_obj->user->data->name, 'JWT obj user prop data prop name prop');
        $this->assertEquals(
            'user@place.com',
            $this->token->jwt_obj->user->data->email,
            'JWT obj user prop data prop email prop'
        );
        $this->assertEquals(
            [
                'role 1',
                'role 2',
            ],
            $this->token->jwt_obj->user->roles,
            'JWT obj user prop data prop roles prop'
        );
        $this->assertTrue(is_int($this->token->jwt_obj->iat), 'JWT obj iat prop');
        $this->assertTrue(is_int($this->token->jwt_obj->exp), 'JWT obj exp prop');
    }

    /**
     * @test
     */
    public function it_makes_a_custom_jwt_with_a_project()
    {
        $user = [
            'name' => 'Name',
            'email' => 'user@place.com',
        ];

        $this->token->makeJwt(
            'project',
            'form.id',
            $user,
            [
                'role 1',
                'role 2',
            ],
            $this->configs['jwt']['secret'],
            $this->configs['jwt']['algorithm']
        );

        $this->assertInstanceOf(stdClass::class, $this->token->jwt_obj->project, 'JWT obj project prop');
        $this->assertEquals('project', $this->token->jwt_obj->project->_id, 'JWT obj form prop _id prop');
    }
}
