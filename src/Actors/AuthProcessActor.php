<?php

namespace MagicAuth\Actors;

use Dapr\Actors\Actor;
use Dapr\Actors\Attributes\DaprType;
use Dapr\Actors\Timer;
use Dapr\DaprClient;
use JetBrains\PhpStorm\Pure;
use MagicAuth\Constants\AuthConstants;
use MagicAuth\Includes\AuthProcessActorInterface;
use MagicAuth\State\AuthState;
use MagicAuth\State\LoginState;
use MagicAuth\State\NoncePhonePair;
use Psr\Log\LoggerInterface;

#[DaprType('AuthProcessActor')]
class AuthProcessActor extends Actor implements AuthProcessActorInterface
{

    #[Pure]
    public function __construct(
        string $id,
        private AuthState $state,
        private DaprClient $daprClient
    ) {
        parent::__construct($id);
    }

    public function on_activation(): void
    {
        parent::on_activation();
        $this->create_timer(
            new Timer(
                name: 'deactivation_timer',
                due_time: new \DateInterval('PT'.AuthConstants::getExpirationTime().'S'),
                period: new \DateInterval('PT'.AuthConstants::getExpirationTime().'S'),
                callback: 'cancelOutstandingAuths'
            ),
            $this->daprClient
        );
    }

    public function cancelAuth(string $phoneNumber): void
    {
        $auths = $this->state->waitingAuth;
        foreach ($auths as $key => $auth) {
            if (str_starts_with(haystack: $key, needle: $phoneNumber)) {
                unset($auths[$key]);
            }
        }
        $this->state->waitingAuth = $auths;
    }

    public function cancelOutstandingAuths()
    {
        foreach ($this->state->waitingAuth as $key => $auth) {
            if ($auth->activationTime + AuthConstants::getExpirationTime() > time()) {
                unset($this->state->waitingAuth[$key]);
            }
        }
    }

    public function start(NoncePhonePair $device): string
    {
        $auths = $this->state->waitingAuth ?? [];
        $key   = $device->phoneNumber.'.'.$device->nonce;

        if ($auths[$key] ?? false) {
            throw new \LogicException('Cannot restart an authentication');
        }

        $current_auth = $auths[$key] = new LoginState(
            activationTime: time(),
            remainingTries: AuthConstants::getMaxRetries(),
            code: $this->get_code(),
            nonce: $device->nonce,
            authenticated: false
        );

        $this->state->waitingAuth = $auths;

        if ($callback = AuthConstants::getReadyCallbackUrl()) {
            $this->daprClient->post(
                $callback,
                [
                    'phoneNumber' => $device->phoneNumber,
                    'code'        => $current_auth->code,
                ]
            );
        }

        return $current_auth->code;
    }

    public function isAuthenticated(NoncePhonePair $device): bool
    {
        return ($this->state->waitingAuth[$device->phoneNumber.'.'.$device->nonce] ?? null)?->authenticated ?? false;
    }

    public function authenticate(string $code): bool
    {
        $success = false;
        $auths = $this->state->waitingAuth;
        foreach ($auths as $phoneNumber => &$auth) {
            if ($auth->code === $code
                && $auth->remainingTries > 0
                && $auth->activationTime + AuthConstants::getExpirationTime() > time()) {
                $auth->authenticated = $success = true;
                if ($callbackUrl = AuthConstants::getSuccessCallbackUrl()) {
                    $this->daprClient->post(
                        $callbackUrl,
                        [
                            'userId'      => $this->id,
                            'phoneNumber' => explode('.', $phoneNumber, 2)[0],
                            'code'        => $auth->code,
                            'nonce'       => $auth->nonce,
                        ]
                    );
                }
            }
        }
        foreach ($auths as $phoneNumber => &$auth) {
            $auth->remainingTries -= $success ? 0 : 1;
        }

        if ( ! $success && $callbackUrl = AuthConstants::getFailedCallbackUrl()) {
            $this->daprClient->post(
                $callbackUrl,
                [
                    'userId'      => $this->id,
                    'phoneNumber' => explode('.', $phoneNumber, 2)[0],
                    'code'        => $auth->code,
                    'nonce'       => $auth->nonce,
                ]
            );
        }

        $this->state->waitingAuth = $auths;

        return $success;
    }

    private function get_code(): string
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }
}
