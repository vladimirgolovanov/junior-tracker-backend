<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\ChildUser\Repository\ChildUserRepositoryInterface;
use App\Domain\ChildUser\ValueObject\ChildUser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DoctrineChildUserRepository implements ChildUserRepositoryInterface
{
    /**
     * Guard shared by demotion and removal: another owner must survive the
     * statement. Folding it into the WHERE clause makes the check and the write
     * a single atomic statement, so two concurrent demotions cannot both see an
     * owner that the other is about to take away.
     */
    private const ANOTHER_OWNER_REMAINS = 'EXISTS (
        SELECT 1 FROM child_users other
        WHERE other.child_id = :childId AND other.user_id <> :userId AND other.is_owner = true
    )';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findAllByChild(int $childId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT cu.user_id, u.email, cu.is_owner
             FROM child_users cu
             INNER JOIN users u ON u.id = cu.user_id
             WHERE cu.child_id = :childId
             ORDER BY cu.is_owner DESC, cu.user_id',
            ['childId' => $childId],
        );

        return array_map($this->toChildUser(...), $rows);
    }

    public function find(int $childId, int $userId): ?ChildUser
    {
        $row = $this->connection->fetchAssociative(
            'SELECT cu.user_id, u.email, cu.is_owner
             FROM child_users cu
             INNER JOIN users u ON u.id = cu.user_id
             WHERE cu.child_id = :childId AND cu.user_id = :userId',
            ['childId' => $childId, 'userId' => $userId],
        );

        return false === $row ? null : $this->toChildUser($row);
    }

    public function updateOwnership(int $childId, int $userId, bool $isOwner): bool
    {
        // Promoting can never leave the child ownerless, so it carries no guard.
        $guard = $isOwner ? '' : ' AND '.self::ANOTHER_OWNER_REMAINS;

        $affected = $this->connection->executeStatement(
            'UPDATE child_users SET is_owner = :isOwner
             WHERE child_id = :childId AND user_id = :userId'.$guard,
            ['isOwner' => $isOwner, 'childId' => $childId, 'userId' => $userId],
            ['isOwner' => ParameterType::BOOLEAN],
        );

        return $affected > 0;
    }

    public function remove(int $childId, int $userId): bool
    {
        // A plain member can always go; an owner only if another one remains.
        $affected = $this->connection->executeStatement(
            'DELETE FROM child_users
             WHERE child_id = :childId AND user_id = :userId
               AND (is_owner = false OR '.self::ANOTHER_OWNER_REMAINS.')',
            ['childId' => $childId, 'userId' => $userId],
        );

        return $affected > 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toChildUser(array $row): ChildUser
    {
        return new ChildUser(
            userId: (int) $row['user_id'],
            email: (string) $row['email'],
            isOwner: (bool) $row['is_owner'],
        );
    }
}
