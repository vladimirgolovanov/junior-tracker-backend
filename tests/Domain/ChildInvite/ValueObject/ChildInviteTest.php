<?php

declare(strict_types=1);

namespace App\Tests\Domain\ChildInvite\ValueObject;

use App\Domain\ChildInvite\Exception\InviteAlreadyAccepted;
use App\Domain\ChildInvite\Exception\InviteExpired;
use App\Domain\ChildInvite\ValueObject\ChildInvite;
use PHPUnit\Framework\TestCase;

/**
 * Когда по приглашению можно присоединиться, а когда нет.
 */
final class ChildInviteTest extends TestCase
{
    public function testAcceptsPendingInviteBeforeExpiry(): void
    {
        $invite = new ChildInvite(1, 42, acceptedAt: null, expiresAt: new \DateTimeImmutable('2026-07-30 10:00'));

        $invite->ensureAcceptable(new \DateTimeImmutable('2026-07-23 10:00'));

        $this->expectNotToPerformAssertions();
    }

    public function testRejectsExpiredInvite(): void
    {
        $invite = new ChildInvite(1, 42, acceptedAt: null, expiresAt: new \DateTimeImmutable('2026-07-23 10:00'));

        $this->expectException(InviteExpired::class);

        $invite->ensureAcceptable(new \DateTimeImmutable('2026-07-23 10:00'));
    }

    public function testRejectsAlreadyAcceptedInvite(): void
    {
        $invite = new ChildInvite(
            1,
            42,
            acceptedAt: new \DateTimeImmutable('2026-07-22 10:00'),
            expiresAt: new \DateTimeImmutable('2026-07-30 10:00'),
        );

        $this->expectException(InviteAlreadyAccepted::class);

        $invite->ensureAcceptable(new \DateTimeImmutable('2026-07-23 10:00'));
    }
}
