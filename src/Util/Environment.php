<?php

declare(strict_types=1);

namespace phpClub\Util;

class Environment
{
    private const PROD = 'prod';
    private const TEST = 'test';

    public static function isTest(): bool
    {
        return getenv('APP_ENV') === self::TEST;
    }

    public static function isProd(): bool
    {
        return getenv('APP_ENV') === self::PROD;
    }
}