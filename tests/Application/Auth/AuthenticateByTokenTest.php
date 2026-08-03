<?php

declare(strict_types=1);

namespace App\Tests\Application\Auth;

use App\Application\Auth\AuthenticateByToken;
use App\Domain\Auth\Exception\Unauthenticated;
use App\Domain\Auth\Repository\AccessTokenRepositoryInterface;
use App\Domain\Auth\ValueObject\AccessToken;
use PHPUnit\Framework\TestCase;

final class AuthenticateByTokenTest extends TestCase
{
    private const TTL = 1209600; // 14 дней

    public function testReturnsUserIdForValidToken(): void
    {
        $repository = $this->repository(userId: 7);

        $userId = (new AuthenticateByToken($repository, self::TTL))(
            'valid-token',
            new \DateTimeImmutable('2026-07-30 12:00:00', new \DateTimeZone('UTC')),
        );

        self::assertSame(7, $userId);
    }

    public function testAppliesTtlAsLowerBoundOnCreatedAt(): void
    {
        $repository = $this->repository(userId: 7);

        (new AuthenticateByToken($repository, self::TTL))(
            'valid-token',
            new \DateTimeImmutable('2026-07-30 12:00:00', new \DateTimeZone('UTC')),
        );

        // now - 14 дней
        self::assertSame('2026-07-16 12:00:00', $repository->notOlderThan?->format('Y-m-d H:i:s'));
        self::assertSame('valid-token', $repository->askedToken);
    }

    public function testThrowsWhenTokenUnknownOrExpired(): void
    {
        $this->expectException(Unauthenticated::class);

        (new AuthenticateByToken($this->repository(userId: null), self::TTL))(
            'nope',
            new \DateTimeImmutable('2026-07-30 12:00:00', new \DateTimeZone('UTC')),
        );
    }

    private function repository(?int $userId)
    {
        return new class($userId) implements AccessTokenRepositoryInterface {
            public ?string $askedToken = null;
            public ?\DateTimeImmutable $notOlderThan = null;

            public function __construct(
                private ?int $userId,
            ) {
            }

            public function save(AccessToken $token): void
            {
            }

            public function findUserId(string $token, \DateTimeImmutable $notOlderThan): ?int
            {
                $this->askedToken = $token;
                $this->notOlderThan = $notOlderThan;

                return $this->userId;
            }
        };
    }
}
