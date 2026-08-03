<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class Unauthenticated extends \RuntimeException
{
    public static function missingBearer(): self
    {
        return new self('Missing or malformed Authorization: Bearer header.');
    }

    public static function invalidToken(): self
    {
        return new self('Access token is invalid or expired.');
    }
}
