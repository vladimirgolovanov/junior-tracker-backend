<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\Exception\EventTypeNotFound;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;
use App\Domain\Event\Repository\EventTypeRepositoryInterface;
use App\Domain\Event\ValueObject\EventType;
use App\Domain\Event\ValueObject\RangeEventType;
use Doctrine\DBAL\Connection;

final readonly class DoctrineEventTypeRepository implements EventTypeRepositoryInterface, EventTypeReadRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function listByChild(int $childId): array
    {
        // to_json(keywords) отдаёт корректный JSON-массив (или null),
        // без ручного разбора Postgres-литерала text[] вида {a,b}.
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, child_id, name, format, color, parent_id, to_json(keywords) AS keywords
             FROM event_types
             WHERE child_id = :childId
             ORDER BY id',
            ['childId' => $childId],
        );

        return array_map(
            static function (array $row): EventType {
                /** @var string[]|null $keywords */
                $keywords = null === $row['keywords'] ? null : json_decode($row['keywords'], true);

                return new EventType(
                    id: (int) $row['id'],
                    childId: (int) $row['child_id'],
                    name: $row['name'],
                    format: $row['format'],
                    color: $row['color'],
                    parentId: null === $row['parent_id'] ? null : (int) $row['parent_id'],
                    keywords: $keywords,
                );
            },
            $rows,
        );
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
