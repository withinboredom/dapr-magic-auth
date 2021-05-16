<?php

use MagicAuth\Actors\AuthProcessActor;

use function DI\autowire;
use function DI\get;

return [
    'dapr.actors'                     => fn() => [AuthProcessActor::class],
    'dapr.actors.idle_timeout'        => new DateInterval('PT10M'),
    'dapr.actors.drain_timeout'       => new DateInterval('PT30S'),
    'dapr.actors.drain_enabled'       => true,
];
