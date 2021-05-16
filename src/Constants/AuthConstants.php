<?php

namespace MagicAuth\Constants;

abstract class AuthConstants
{
    public const EXPIRATION_KEY = 'AUTH_EXPIRATION_TIME';
    public const RETRIES_KEY = 'AUTH_MAX_RETRIES';
    public const AUTH_SUCCESS_CALLBACK = 'AUTH_SUCCESS_CALLBACK';
    public const AUTH_READY_CALLBACK = 'AUTH_READY_CALLBACK';
    public const AUTH_FAILED_CALLBACK = 'AUTH_FAILED_CALLBACK';

    public static function getExpirationTime(): int
    {
        return (int)self::readEnv(self::EXPIRATION_KEY, 300);
    }

    private static function readEnv(string $key, mixed $default): mixed
    {
        static $vals = [];

        return $vals[$key] ??= getenv($key) ?: $default;
    }

    public static function getMaxRetries(): int
    {
        return (int)self::readEnv(self::RETRIES_KEY, 3);
    }

    public static function getSuccessCallbackUrl(): string|null
    {
        return self::readEnv(self::AUTH_SUCCESS_CALLBACK, null);
    }

    public static function getReadyCallbackUrl(): string|null {
        return self::readEnv(self::AUTH_READY_CALLBACK, null);
    }

    public static function getFailedCallbackUrl(): string|null {
        return self::readEnv(self::AUTH_FAILED_CALLBACK, null);
    }
}
