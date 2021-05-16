<?php

namespace MagicAuth\Services;

use Dapr\Actors\ActorProxy;
use MagicAuth\Includes\AuthProcessActorInterface;
use MagicAuth\State\NoncePhonePair;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

class AuthService
{
    public function __construct(private ActorProxy $proxy)
    {
    }

    public function beginAuth(string $userId, string $phoneNumber, string $nonce): ResponseInterface|array
    {
        try {
            $code = $this->proxy->get(AuthProcessActorInterface::class, $userId)->start(
                new NoncePhonePair($nonce, $phoneNumber)
            );

            return new Response(body: json_encode(['code' => $code]));
        } catch (\LogicException $ex) {
            return new Response(status: 403);
        }
    }

    public function cancelAuth(string $userId, string $phoneNumber): void
    {
        $this->proxy->get(AuthProcessActorInterface::class, $userId)->cancelAuth($phoneNumber);
    }

    public function isAuthenticated(string $userId, string $phoneNumber, string $nonce): array
    {
        $result = $this->proxy->get(AuthProcessActorInterface::class, $userId)->isAuthenticated(
            new NoncePhonePair($nonce, $phoneNumber)
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
