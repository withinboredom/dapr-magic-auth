<?php

namespace MagicAuth\State;

use Dapr\Actors\ActorState;
use Dapr\Deserialization\Attributes\ArrayOf;

class AuthState extends ActorState
{
    /**
     * @var LoginState[]
     */
    #[ArrayOf(LoginState::class)]
    public array $waitingAuth = [];
}
