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
    '/start/{userId}/{phoneNumber}/{nonce}',
    fn(string $userId, string $phoneNumber, string $nonce, ActorProxy $proxy) => (new AuthService($proxy))->beginAuth(
        $userId,
        $phoneNumber,
        $nonce
    )
);
$app->post(
    '/cancel/{userId}/{phoneNumber}',
    fn(string $userId, string $phoneNumber, ActorProxy $proxy) => (new AuthService($proxy))->cancelAuth(
        $userId,
        $phoneNumber
    )
);
$app->get(
    '/isAuthenticated/{userId}/{phoneNumber}/{nonce}',
    fn(string $userId, string $phoneNumber, string $nonce, ActorProxy $proxy) => (new AuthService(
        $proxy
    ))->isAuthenticated($userId, $phoneNumber, $nonce)
);
$app->post(
    '/authenticate/{userId}/{code}',
    fn(string $userId, string $code, ActorProxy $proxy) => (new AuthService($proxy))->authenticate($userId, $code)
);

$app->start();
