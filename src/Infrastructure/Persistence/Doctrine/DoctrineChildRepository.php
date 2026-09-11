<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Child\Repository\ChildRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineChildRepository implements ChildRepositoryInterface, ChildAccessRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findTimezone(int $childId): \DateTimeZone
    {
        $timezone = $this->connection->fetchOne(
            'SELECT timezone FROM childs WHERE id = :id',
            ['id' => $childId],
        );

        return new \DateTimeZone($timezone);
    }

    public function findName(int $childId): string
    {
        return (string) $this->connection->fetchOne(
            'SELECT name FROM childs WHERE id = :id',
            ['id' => $childId],
        );
    }

    public function userHasAccessToChild(int $userId, int $childId): bool
    {
        return false !== $this->connection->fetchOne(
            'SELECT 1 FROM child_users WHERE user_id = :userId AND child_id = :childId',
            ['userId' => $userId, 'childId' => $childId],
        );
    }

    public function userIsOwnerOfChild(int $userId, int $childId): bool
    {
        return false !== $this->connection->fetchOne(
            'SELECT 1 FROM child_users WHERE user_id = :userId AND child_id = :childId AND is_owner = true',
            ['userId' => $userId, 'childId' => $childId],
        );
    }
}
