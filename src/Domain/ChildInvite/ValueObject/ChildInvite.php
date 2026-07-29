<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\ValueObject;

use App\Domain\ChildInvite\Exception\InviteAlreadyAccepted;
use App\Domain\ChildInvite\Exception\InviteExpired;

/**
 * Прочитанное из БД приглашение — ровно то, что нужно, чтобы решить,
 * можно ли по нему присоединиться к ребёнку.
 */
final readonly class ChildInvite
{
    public function __construct(
        public int $id,
        public int $childId,
        public ?\DateTimeImmutable $acceptedAt,
        public \DateTimeImmutable $expiresAt,
    ) {
    }

    /**
     * @throws InviteAlreadyAccepted
     * @throws InviteExpired
     */
    public function ensureAcceptable(\DateTimeImmutable $now): void
    {
        if (null !== $this->acceptedAt) {
            throw InviteAlreadyAccepted::create();
        }

        if ($now >= $this->expiresAt) {
            throw InviteExpired::create();
        }
    }
}
