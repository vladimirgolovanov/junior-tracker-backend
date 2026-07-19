<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\Exception\EventTypeNotFound;
use App\Domain\Event\Repository\EventTypeRepositoryInterface;
use App\Domain\Event\ValueObject\RangeEventType;
use Doctrine\DBAL\Connection;

final readonly class DoctrineEventTypeRepository implements EventTypeRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findRangeType(int $childId, string $startName): RangeEventType
    {
        $row = $this->connection->fetchAssociative(
            'SELECT s.id AS start_id, e.id AS end_id
             FROM event_types s
             JOIN event_types e ON e.parent_id = s.id
             WHERE s.child_id = :childId AND s.name = :startName',
            ['childId' => $childId, 'startName' => $startName],
        );

        if (false === $row) {
            throw EventTypeNotFound::rangeType($childId, $startName);
        }

        return new RangeEventType((int) $row['start_id'], (int) $row['end_id']);
    }

    public function findPlainType(int $childId, string $name): int
    {
        $id = $this->connection->fetchOne(
            'SELECT id FROM event_types WHERE child_id = :childId AND name = :name',
            ['childId' => $childId, 'name' => $name],
        );

        if (false === $id) {
            throw EventTypeNotFound::plainType($childId, $name);
        }

        return (int) $id;
    }
}
