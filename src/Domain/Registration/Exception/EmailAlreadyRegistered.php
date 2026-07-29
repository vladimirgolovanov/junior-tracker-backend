<?php

declare(strict_types=1);

namespace App\Domain\Registration\Exception;

final class EmailAlreadyRegistered extends \DomainException
{
    public static function create(): self
    {
        return new self('A user with this email already exists.');
    }
}
