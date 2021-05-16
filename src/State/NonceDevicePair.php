<?php

namespace MagicAuth\State;

class NonceDevicePair {
    public function __construct(public string $nonce, public string $deviceId) {}
}
