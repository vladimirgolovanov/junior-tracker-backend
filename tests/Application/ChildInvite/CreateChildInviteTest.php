<?php

declare(strict_types=1);

namespace App\Tests\Application\ChildInvite;

use App\Application\ChildInvite\CreateChildInvite;
use App\Domain\ChildInvite\Repository\ChildInviteRepositoryInterface;
use App\Domain\ChildInvite\Service\ChildInviteFactory;
use App\Domain\ChildInvite\Service\InviteCodeGeneratorInterface;
use App\Domain\ChildInvite\ValueObject\ChildInvite;
use App\Domain\ChildInvite\ValueObject\NewChildInvite;
use PHPUnit\Framework\TestCase;

final class CreateChildInviteTest extends TestCase
{
    public function testPersistsInviteAndReturnsCodeWithExpiry(): void
    {
        $repository = $this->repository();
        $factory = new ChildInviteFactory($this->codeGenerator('the-code'), ttlSeconds: 3600);
        $now = new \DateTimeImmutable('2026-07-23 10:00');

        $created = (new CreateChildInvite($factory, $repository))->handle(
            childId: 42,
            inviterId: 5,
            now: $now,
        );

        self::assertSame('the-code', $created->code);
        self::assertSame($now->getTimestamp() + 3600, $created->expiresAt->getTimestamp());

        self::assertSame('the-code', $repository->saved?->code);
        self::assertSame(42, $repository->saved?->childId);
        self::assertSame(5, $repository->saved?->inviterId);
    }

    private function repository()
    {
        return new class implements ChildInviteRepositoryInterface {
            public ?NewChildInvite $saved = null;

            public function create(NewChildInvite $invite): void
            {
                $this->saved = $invite;
            }

            public function findByCode(string $code): ChildInvite
            {
                throw new \LogicException('findByCode is not used here.');
            }

            public function markAccepted(int $inviteId, int $userId, \DateTimeImmutable $now): void
            {
                throw new \LogicException('markAccepted is not used here.');
            }
        };
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
