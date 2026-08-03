<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Auth\Repository\AccessTokenRepositoryInterface;
use App\Domain\Auth\ValueObject\AccessToken;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final readonly class DoctrineAccessTokenRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function save(AccessToken $token): void
    {
        // access_tokens.created_at — timestamp WITH time zone (в отличие от users),
        // поэтому пишем tz-aware момент типом datetimetz_immutable.
        $this->connection->executeStatement(
            'INSERT INTO access_tokens (user_id, token, created_at) VALUES (:userId, :token, :createdAt)',
            [
                'userId' => $token->userId,
                'token' => $token->value,
                'createdAt' => $token->createdAt,
            ],
            [
                'createdAt' => Types::DATETIMETZ_IMMUTABLE,
            ],
        );
    }

    public function findUserId(string $token, \DateTimeImmutable $notOlderThan): ?int
    {
        $userId = $this->connection->fetchOne(
            'SELECT user_id FROM access_tokens WHERE token = :token AND created_at >= :notOlderThan',
            [
                'token' => $token,
                'notOlderThan' => $notOlderThan,
            ],
            [
                'notOlderThan' => Types::DATETIMETZ_IMMUTABLE,
            ],
        );

        return false === $userId ? null : (int) $userId;
    }
}
