<?php

use MagicAuth\Constants\AuthConstants;
use PHPUnit\Framework\TestCase;

class AuthConstantsTest extends TestCase
{
    public function testDefaultExpirationTime()
    {
        $this->assertSame(300, AuthConstants::getExpirationTime());
    }

    public function testDefaultMaxRetries()
    {
        $this->assertSame(3, AuthConstants::getMaxRetries());
    }

    public function testDefaultSuccessCallback()
    {
        $this->assertNull(AuthConstants::getSuccessCallbackUrl());
    }

    public function testDefaultReadyCallback()
    {
        $this->assertNull(AuthConstants::getReadyCallbackUrl());
    }
}
