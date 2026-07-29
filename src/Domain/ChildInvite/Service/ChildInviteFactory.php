<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\Service;

use App\Domain\ChildInvite\ValueObject\NewChildInvite;

/**
 * Правило создания приглашения: какой код и на какой срок.
 * Проверяется unit-тестом без базы.
 */
final readonly class ChildInviteFactory
{
    public function __construct(
        private InviteCodeGeneratorInterface $codeGenerator,
        private int $ttlSeconds,
    ) {
    }

    public function create(int $inviterId, int $childId, \DateTimeImmutable $now): NewChildInvite
    {
        return new NewChildInvite(
            code: $this->codeGenerator->generate(),
            inviterId: $inviterId,
            childId: $childId,
            createdAt: $now,
            expiresAt: $now->add(new \DateInterval(sprintf('PT%dS', $this->ttlSeconds))),
        );
    }
}
