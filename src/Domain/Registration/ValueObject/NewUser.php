<?php

declare(strict_types=1);

namespace App\Domain\Registration\ValueObject;

use App\Domain\User\ValueObject\Email;

final readonly class NewUser
{
    public function __construct(
        public Email $email,
        public string $hashedPassword,
        public bool $isActive,
        public bool $isSuperuser,
        public bool $isVerified,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
