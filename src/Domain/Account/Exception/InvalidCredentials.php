<?php

declare(strict_types=1);

namespace App\Domain\Account\Exception;

/**
 * Single failure for both a wrong password and a missing email: the public
 * deletion form must not reveal whether an address is registered.
 */
final class InvalidCredentials extends \RuntimeException
{
    public static function create(): self
    {
        return new self('Email or password is incorrect.');
    }
}
