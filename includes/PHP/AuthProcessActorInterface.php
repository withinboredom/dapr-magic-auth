<?php

namespace MagicAuth\Includes;

use Dapr\Actors\Attributes\DaprType;
use MagicAuth\State\NoncePhonePair;

#[DaprType('AuthProcessActor')]
interface AuthProcessActorInterface
{
    /**
     * Cancel all current auths for a phone number
     *
     * @param string $phoneNumber The phone number to cancel
     */
    public function cancelAuth(string $phoneNumber): void;

    /**
     * Start an authentication flow
     *
     * @param NoncePhonePair $device
     *
     * @return string The code
     */
    public function start(NoncePhonePair $device): string;

    /**
     * Check whether an authenticated flow is authenticated
     *
     * @param NoncePhonePair $device
     *
     * @return bool
     */
    public function isAuthenticated(NoncePhonePair $device): bool;

    /**
     * Authenticate with a code
     *
     * @param string $code The code
     *
     * @return bool Whether the authentication is successful
     */
    public function authenticate(string $code): bool;
}
