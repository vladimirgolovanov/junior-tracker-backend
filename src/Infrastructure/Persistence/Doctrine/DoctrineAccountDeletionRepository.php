<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Account\Repository\AccountDeletionRepositoryInterface;
use App\Domain\Account\ValueObject\DeletedAccount;
use App\Domain\Account\ValueObject\StoredCredentials;
use App\Domain\User\ValueObject\Email;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * The schema is owned by the FastAPI service and only three foreign keys carry
 * ON DELETE CASCADE (child_users.child_id, child_users.user_id,
 * access_tokens.user_id). Everything else is NO ACTION, so every dependent row
 * has to be removed here explicitly, in foreign-key order.
 */
final readonly class DoctrineAccountDeletionRepository implements AccountDeletionRepositoryInterface
{
    /**
     * Child-scoped tables, ordered so that no delete leaves a dangling
     * reference: events point at event_types, everything points at childs.
     * event_types is self-referencing through parent_id, which a single
     * statement handles — PostgreSQL checks referential integrity once the
     * statement is done, not row by row.
     */
    private const CHILD_SCOPED_DELETES = [
        'DELETE FROM events WHERE child_id IN (:childIds)',
        'DELETE FROM event_types WHERE child_id IN (:childIds)',
        'DELETE FROM sleep_predicts WHERE child_id IN (:childIds)',
        'DELETE FROM daily_analytics WHERE child_id IN (:childIds)',
        'DELETE FROM api_keys WHERE child_id IN (:childIds)',
        'DELETE FROM child_invites WHERE child_id IN (:childIds)',
        // child_users and exports rows disappear with the child through
        // ON DELETE CASCADE (their stored S3 files are cleaned up separately).
        'DELETE FROM childs WHERE id IN (:childIds)',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findCredentialsByEmail(Email $email): ?StoredCredentials
    {
        // lower(email) mirrors how FastAPI looks users up: ix_users_email is
        // case-sensitive, so matching by the raw value would miss "A@x.ru".
        $row = $this->connection->fetchAssociative(
            'SELECT id, hashed_password FROM users WHERE lower(email) = :email',
            ['email' => $email->lowercased()],
        );

        return false === $row ? null : new StoredCredentials((int) $row['id'], (string) $row['hashed_password']);
    }

    public function findCredentialsById(int $userId): ?StoredCredentials
    {
        $hashedPassword = $this->connection->fetchOne(
            'SELECT hashed_password FROM users WHERE id = :id',
            ['id' => $userId],
        );

        return false === $hashedPassword ? null : new StoredCredentials($userId, (string) $hashedPassword);
    }

    public function delete(int $userId): DeletedAccount
    {
        return $this->connection->transactional(
            function (Connection $connection) use ($userId): DeletedAccount {
                $childIds = $this->childrenToPurge($connection, $userId);

                if ([] !== $childIds) {
                    foreach (self::CHILD_SCOPED_DELETES as $sql) {
                        $connection->executeStatement(
                            $sql,
                            ['childIds' => $childIds],
                            ['childIds' => ArrayParameterType::INTEGER],
                        );
                    }
                }

                // Rows that reference the user directly and would block the
                // final delete: invites they created or accepted for children
                // that survive, plus their API keys.
                $connection->executeStatement(
                    'DELETE FROM child_invites WHERE inviter_id = :userId OR accepted_by_id = :userId',
                    ['userId' => $userId],
                );
                $connection->executeStatement(
                    'DELETE FROM api_keys WHERE user_id = :userId',
                    ['userId' => $userId],
                );

                // child_users and access_tokens follow through ON DELETE CASCADE.
                $connection->executeStatement(
                    'DELETE FROM users WHERE id = :userId',
                    ['userId' => $userId],
                );

                return new DeletedAccount($userId, $childIds);
            },
        );
    }

    /**
     * Children that must go with the user: the ones they own, plus the ones
     * they are the last member of. Without the second case the child would
     * survive with an empty child_users, and since every read path goes through
     * that table, it would be unreachable data nobody can ever delete.
     *
     * @return int[]
     */
    private function childrenToPurge(Connection $connection, int $userId): array
    {
        $childIds = $connection->fetchFirstColumn(
            'SELECT cu.child_id
             FROM child_users cu
             WHERE cu.user_id = :userId
               AND (cu.is_owner = true
                    OR NOT EXISTS (
                        SELECT 1 FROM child_users other
                        WHERE other.child_id = cu.child_id AND other.user_id <> :userId
                    ))
             FOR UPDATE',
            ['userId' => $userId],
        );

        return array_map(intval(...), $childIds);
    }
}
