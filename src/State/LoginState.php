<?php

namespace MagicAuth\State;

class LoginState
{
    public function __construct(
        public int $activationTime,
        public int $remainingTries,
        public string $code,
        public string $nonce,
        public bool $authenticated = false
    ) {
    }
}
