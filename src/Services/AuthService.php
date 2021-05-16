<?php

namespace MagicAuth\Services;

use Dapr\Actors\ActorProxy;
use MagicAuth\Includes\AuthProcessActorInterface;
use MagicAuth\State\NonceDevicePair;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class AuthService
{
    public function __construct(private ActorProxy $proxy)
    {
    }

    public function beginAuth(string $userId, string $deviceId, string $nonce): ResponseInterface|array
    {
        try {
            $code = $this->proxy->get(AuthProcessActorInterface::class, $userId)->start(
                new NonceDevicePair($nonce, $deviceId)
            );

            return new Response(body: json_encode(['code' => $code]));
        } catch (\LogicException $ex) {
            return new Response(status: 403);
        }
    }

    public function cancelAuth(string $userId, string $deviceId): void
    {
        $this->proxy->get(AuthProcessActorInterface::class, $userId)->cancelAuth($deviceId);
    }

    public function isAuthenticated(string $userId, string $deviceId, string $nonce): array
    {
        $result = $this->proxy->get(AuthProcessActorInterface::class, $userId)->isAuthenticated(
            new NonceDevicePair($nonce, $deviceId)
        );

        return [
            'isAuthenticated' => $result,
        ];
    }

    public function authenticate(string $userId, string $code): array
    {
        $result = $this->proxy->get(AuthProcessActorInterface::class, $userId)->authenticate($code);

        return [
            'isAuthenticated' => $result,
        ];
    }
}
