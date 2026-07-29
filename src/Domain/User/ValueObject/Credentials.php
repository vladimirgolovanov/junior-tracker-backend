<?php

declare(strict_types=1);

namespace App\Domain\User\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;

final readonly class Credentials
{
    public function __construct(
        public Email $email,
        public PlainPassword $password,
    ) {
        if (mb_strtolower($password->value) === $email->lowercased()) {
            throw InvalidValue::passwordEqualsEmail();
        }
    }
}
