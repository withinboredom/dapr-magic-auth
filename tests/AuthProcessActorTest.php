<?php

require_once __DIR__.'/DaprTestBase.php';

use DI\Container;
use MagicAuth\Actors\AuthProcessActor;
use MagicAuth\State\AuthState;
use MagicAuth\State\LoginState;
use MagicAuth\State\NonceDevicePair;

class AuthProcessActorTest extends DaprTestBase
{
    protected AuthState $state;
    protected string $id;

    public function setUp(): void
    {
        parent::setUp();
        $this->container = new Container();
        $this->state     = new AuthState($this->container, $this->container);
        $this->id        = uniqid('user_');
    }

    public function testCancelAuthNoExists()
    {
        $actor = new AuthProcessActor($this->id, $this->state, $this->daprClient);
        $actor->cancelAuth('###-###-####');
        $this->assertSame([], $this->state->waitingAuth);
    }

    public function testStartAuth()
    {
        $actor = new AuthProcessActor($this->id, $this->state, $this->daprClient);
        $actor->start(new NonceDevicePair('123', '###'));
        $this->assertSame(['###.123'], array_keys($this->state->waitingAuth));
        $this->assertLoginState(new LoginState(123, 3, 'code', '123'), $this->state->waitingAuth['###.123']);
    }

    protected function assertLoginState(LoginState $expected, LoginState $actual)
    {
        $actual   = array_merge(
            (array)$actual,
            ['activationTime' => $expected->activationTime, 'code' => $expected->code]
        );
        $expected = (array)$expected;
        $this->assertSame($expected, $actual);
    }

    public function testStartAuthFailed()
    {
        $actor = new AuthProcessActor($this->id, $this->state, $this->daprClient);
        $actor->start(new NonceDevicePair('123', '###'));
        $this->assertSame(['###.123'], array_keys($this->state->waitingAuth));
        $this->expectException(LogicException::class);
        $actor->start(new NonceDevicePair('123', '###'));
    }

    public function testIsAuthenticated()
    {
        $actor = new AuthProcessActor($this->id, $this->state, $this->daprClient);
        $this->assertFalse($actor->isAuthenticated(new NonceDevicePair('123', '###')));
        $this->state->waitingAuth['###.123'] = new LoginState(123, 3, 'code', '123', true);
        $this->assertTrue($actor->isAuthenticated(new NonceDevicePair('123', '###')));
    }

    public function getLoginStates()
    {
        return [
            'invalid code'  => [
                new LoginState(time(), 3, 'code', '123'),
                '145',
                new LoginState(123, 2, 'code', '123'),
            ],
            'valid code'    => [
                new LoginState(time(), 3, 'code', '123'),
                'code',
                new LoginState(123, 3, 'code', '123', true),
                true,
            ],
            'no retries'    => [
                new LoginState(time(), 0, 'code', '123'),
                'code',
                new LoginState(123, -1, 'code', '123'),
            ],
            'took too long' => [
                new LoginState(123, 3, 'code', '123'),
                'code',
                new LoginState(123, 2, 'code', '123'),
            ],
        ];
    }

    /**
     * @dataProvider getLoginStates
     */
    public function testAuthenticateIncorrectCode($initialLogin, $code, $expectedResult, $isSuccess = false)
    {
        $actor                               = new AuthProcessActor($this->id, $this->state, $this->daprClient);
        $this->state->waitingAuth['###.123'] = $initialLogin;
        $this->assertSame($isSuccess, $actor->authenticate($code));
        $this->assertLoginState($expectedResult, $this->state->waitingAuth['###.123']);
    }
}
