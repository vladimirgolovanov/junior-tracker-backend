<?php

declare(strict_types=1);

namespace App\Tests\Application\Auth;

use App\Application\Auth\IssueAccessToken;
use App\Domain\Auth\Repository\AccessTokenRepositoryInterface;
use App\Domain\Auth\Service\TokenValueGeneratorInterface;
use App\Domain\Auth\ValueObject\AccessToken;
use PHPUnit\Framework\TestCase;

final class IssueAccessTokenTest extends TestCase
{
    public function testIssuesTokenForUserAndPersistsIt(): void
    {
        $repository = $this->accessTokenRepository();

        $token = (new IssueAccessToken($this->generator('fixed-token-value'), $repository))(
            userId: 42,
            now: new \DateTimeImmutable('2026-07-30 12:00', new \DateTimeZone('Europe/Moscow')),
        );

        self::assertSame('fixed-token-value', $token->value);
        self::assertSame(42, $token->userId);

        // access_tokens.created_at — timestamp with time zone; фиксируем UTC.
        self::assertSame('UTC', $token->createdAt->getTimezone()->getName());
        self::assertSame('2026-07-30 09:00', $token->createdAt->format('Y-m-d H:i'));

        self::assertSame($token, $repository->saved);
    }

    private function generator(string $value): TokenValueGeneratorInterface
    {
        return new class($value) implements TokenValueGeneratorInterface {
            public function __construct(
                private string $value,
            ) {
            }

            public function generate(): string
            {
                return $this->value;
            }
        };
    }

    private function accessTokenRepository()
    {
        return new class implements AccessTokenRepositoryInterface {
            public ?AccessToken $saved = null;

            public function save(AccessToken $token): void
            {
                $this->saved = $token;
            }

            public function findUserId(string $token, \DateTimeImmutable $notOlderThan): ?int
            {
                return null;
            }
        };
    }
}
