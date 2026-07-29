<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\ChildInvite\Exception\InviteAlreadyAccepted;
use App\Domain\ChildInvite\Exception\InviteNotFound;
use App\Domain\ChildInvite\Repository\ChildInviteRepositoryInterface;
use App\Domain\ChildInvite\ValueObject\ChildInvite;
use App\Domain\ChildInvite\ValueObject\NewChildInvite;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final readonly class DoctrineChildInviteRepository implements ChildInviteRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function create(NewChildInvite $invite): void
    {
        $this->connection->executeStatement(
            'INSERT INTO child_invites (code, inviter_id, child_id, created_at, expires_at)
             VALUES (:code, :inviterId, :childId, :createdAt, :expiresAt)',
            [
                'code' => $invite->code,
                'inviterId' => $invite->inviterId,
                'childId' => $invite->childId,
                'createdAt' => $invite->createdAt,
                'expiresAt' => $invite->expiresAt,
            ],
            [
                'createdAt' => Types::DATETIMETZ_IMMUTABLE,
                'expiresAt' => Types::DATETIMETZ_IMMUTABLE,
            ],
        );
    }

    public function findByCode(string $code): ChildInvite
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, child_id, accepted_at, expires_at FROM child_invites WHERE code = :code',
            ['code' => $code],
        );

        if (false === $row) {
            throw InviteNotFound::create();
        }

        return new ChildInvite(
            id: (int) $row['id'],
            childId: (int) $row['child_id'],
            acceptedAt: null !== $row['accepted_at'] ? new \DateTimeImmutable((string) $row['accepted_at']) : null,
            expiresAt: new \DateTimeImmutable((string) $row['expires_at']),
        );
    }

    public function markAccepted(int $inviteId, int $userId, \DateTimeImmutable $now): void
    {
        // Условие accepted_at IS NULL делает приём одноразовым даже при гонке двух
        // одновременных регистраций по одному коду: второй апдейт затронет 0 строк.
        $affected = $this->connection->executeStatement(
            'UPDATE child_invites
             SET accepted_by_id = :userId, accepted_at = :now
             WHERE id = :id AND accepted_at IS NULL',
            [
                'userId' => $userId,
                'now' => $now,
                'id' => $inviteId,
            ],
            [
                'now' => Types::DATETIMETZ_IMMUTABLE,
            ],
        );

        if (0 === $affected) {
            throw InviteAlreadyAccepted::create();
        }
    }
}
