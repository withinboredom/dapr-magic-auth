<?php

use Dapr\App;
use Dapr\DaprClient;
use Dapr\Mocks\TestClient;
use DI\Container;
use DI\ContainerBuilder;
use MagicAuth\Actors\AuthProcessActor;
use PHPUnit\Framework\TestCase;

use function DI\autowire;

class DaprTestBase extends TestCase {
    protected Container $container;
    protected TestClient $daprClient;
    protected App $app;

    public function setUp(): void
    {
        parent::setUp();
        $app = App::create(
            configure: fn(ContainerBuilder $builder) => $builder->addDefinitions(
            ['dapr.actors' => [AuthProcessActor::class]],
            [DaprClient::class => autowire(TestClient::class)->constructorParameter('port', '0')]
        ));
        $app->run(fn(Container $container) => $this->container = $container);
        $app->run(fn(DaprClient $client) => $this->daprClient = $client);
        $this->app = $app;
    }
}
