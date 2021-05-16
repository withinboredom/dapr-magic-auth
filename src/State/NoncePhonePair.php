<?php

namespace MagicAuth\State;

class NoncePhonePair {
    public function __construct(public string $nonce, public string $phoneNumber) {}
}
