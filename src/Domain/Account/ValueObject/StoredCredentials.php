<?php

declare(strict_types=1);

namespace App\Domain\Account\ValueObject;

final readonly class StoredCredentials
{
    public function __construct(
        public int $userId,
        public string $hashedPassword,
    ) {
    }
}
