<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\ValueObject;

/**
 * Что кладём в child_invites при создании приглашения.
 */
final readonly class NewChildInvite
{
    public function __construct(
        public string $code,
        public int $inviterId,
        public int $childId,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $expiresAt,
    ) {
    }
}
