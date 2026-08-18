<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

use App\Domain\User\ValueObject\PlainPassword;

interface PasswordHasherInterface
{
    public function hash(PlainPassword $password): string;

    /**
     * Takes a raw string rather than PlainPassword on purpose: a password that
     * no longer satisfies today's length rules must still be verifiable, and
     * the caller must see "wrong credentials" instead of a validation error.
     */
    public function verify(string $plainPassword, string $hashedPassword): bool;
}
