<?php

declare(strict_types=1);

namespace App\Domain\Registration\ValueObject;

final readonly class RegisteredUser
{
    public function __construct(
        public int $userId,
        public int $childId,
    ) {
    }
}
