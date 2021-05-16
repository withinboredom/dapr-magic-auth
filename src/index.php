<?php

namespace MagicAuth;

use Dapr\Actors\ActorProxy;
use Dapr\App;
use DI\ContainerBuilder;
use MagicAuth\Services\AuthService;

require_once __DIR__.'/../vendor/autoload.php';

$app = App::create(
    configure: fn(ContainerBuilder $builder) => $builder
    ->addDefinitions(__DIR__.'/config.php')
//->enableCompilation('/tmp')
);

$app->post(
    '/start/{userId}/{deviceId}/{nonce}',
    fn(string $userId, string $deviceId, string $nonce, ActorProxy $proxy) => (new AuthService($proxy))->beginAuth(
        $userId,
        $deviceId,
        $nonce
    )
);
$app->post(
    '/cancel/{userId}/{deviceId}',
    fn(string $userId, string $deviceId, ActorProxy $proxy) => (new AuthService($proxy))->cancelAuth(
        $userId,
        $deviceId
    )
);
$app->get(
    '/isAuthenticated/{userId}/{deviceId}/{nonce}',
    fn(string $userId, string $deviceId, string $nonce, ActorProxy $proxy) => (new AuthService(
        $proxy
    ))->isAuthenticated($userId, $deviceId, $nonce)
);
$app->post(
    '/authenticate/{userId}/{code}',
    fn(string $userId, string $code, ActorProxy $proxy) => (new AuthService($proxy))->authenticate($userId, $code)
);

$app->start();
