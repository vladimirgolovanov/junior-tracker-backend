<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\User\Repository\CurrentUserRepositoryInterface;
use App\Domain\User\ValueObject\ChildMembership;
use App\Domain\User\ValueObject\CurrentUser;
use App\Domain\User\ValueObject\Email;
use Doctrine\DBAL\Connection;

final readonly class DoctrineCurrentUserRepository implements CurrentUserRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function find(int $userId): ?CurrentUser
    {
        // LEFT JOIN so a user with no children still comes back as one row with
        // a null child_id — that state is reachable after being removed from
        // the last child they belonged to.
        $rows = $this->connection->fetchAllAssociative(
            'SELECT u.id, u.email, cu.child_id, cu.is_owner
             FROM users u
             LEFT JOIN child_users cu ON cu.user_id = u.id
             WHERE u.id = :userId
             ORDER BY cu.child_id',
            ['userId' => $userId],
        );

        if ([] === $rows) {
            return null;
        }

        $memberships = [];

        foreach ($rows as $row) {
            if (null !== $row['child_id']) {
                $memberships[] = new ChildMembership((int) $row['child_id'], (bool) $row['is_owner']);
            }
        }

        return new CurrentUser(
            id: (int) $rows[0]['id'],
            email: new Email((string) $rows[0]['email']),
            memberships: $memberships,
        );
    }
}
