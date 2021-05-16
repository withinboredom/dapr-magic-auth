<?php

use Dapr\Actors\ActorProxy;
use MagicAuth\Services\AuthService;

class AuthServiceTest extends DaprTestBase
{
    public AuthService $service;

    public function setUp(): void
    {
        parent::setUp();
        $actorProxy    = $this->app->run(fn(ActorProxy $proxy) => $proxy);
        $this->service = new AuthService($actorProxy);
    }

    public function testStartAuth()
    {
        $this->daprClient->register_post(
            '/actors/AuthProcessActor/123/method/start',
            200,
            'code',
            [
                'nonce'       => '111',
                'phoneNumber' => '###',
            ]
        );
        $data = $this->service->beginAuth('123', '###', '111');
        $this->assertSame(['code' => 'code'], $data);
    }

    public function testCancelAuth()
    {
        $this->daprClient->register_post('/actors/AuthProcessActor/123/method/cancelAuth', 200, null, '###');
        $this->service->cancelAuth('123', '###');
    }

    public function testIsAuthenticated()
    {
        $this->daprClient->register_post(
            '/actors/AuthProcessActor/123/method/isAuthenticated',
            200,
            false,
            ['nonce' => '111', 'phoneNumber' => '###']
        );
        $result = $this->service->isAuthenticated('123', '###', '111');
        $this->assertSame(['isAuthenticated' => false], $result);
    }

    public function testAuthenticate()
    {
        $this->daprClient->register_post('/actors/AuthProcessActor/123/method/authenticate', 200, false, 'code');
        $result = $this->service->authenticate('123', 'code');
        $this->assertSame(['isAuthenticated' => false], $result);
    }
}
