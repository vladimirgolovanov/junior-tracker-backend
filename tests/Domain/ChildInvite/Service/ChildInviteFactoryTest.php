<?php

declare(strict_types=1);

namespace App\Tests\Domain\ChildInvite\Service;

use App\Domain\ChildInvite\Service\ChildInviteFactory;
use App\Domain\ChildInvite\Service\InviteCodeGeneratorInterface;
use PHPUnit\Framework\TestCase;

/**
 * Правило создания приглашения: код от генератора и срок = now + TTL.
 */
final class ChildInviteFactoryTest extends TestCase
{
    public function testBuildsInviteWithGeneratedCodeAndTtl(): void
    {
        $factory = new ChildInviteFactory($this->codeGenerator('fixed-code'), ttlSeconds: 3600);
        $now = new \DateTimeImmutable('2026-07-23 10:00');

        $invite = $factory->create(inviterId: 5, childId: 42, now: $now);

        self::assertSame('fixed-code', $invite->code);
        self::assertSame(5, $invite->inviterId);
        self::assertSame(42, $invite->childId);
        self::assertSame($now->getTimestamp(), $invite->createdAt->getTimestamp());
        self::assertSame($now->getTimestamp() + 3600, $invite->expiresAt->getTimestamp());
    }

    private function codeGenerator(string $code): InviteCodeGeneratorInterface
    {
        return new class($code) implements InviteCodeGeneratorInterface {
            public function __construct(private string $code)
            {
            }

            public function generate(): string
            {
                return $this->code;
            }
        };
    }
}
