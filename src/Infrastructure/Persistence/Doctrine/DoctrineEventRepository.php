<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\Repository\EventReadRepositoryInterface;
use App\Domain\Event\Repository\EventRepositoryInterface;
use App\Domain\Event\Repository\EventWriteRepositoryInterface;
use App\Domain\Event\ValueObject\Event;
use App\Domain\Event\ValueObject\EventDetails;
use App\Domain\Event\ValueObject\EventDraft;
use App\Domain\Event\ValueObject\EventPatch;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

final readonly class DoctrineEventRepository implements EventRepositoryInterface, EventReadRepositoryInterface, EventWriteRepositoryInterface
{
    private const UTC = 'UTC';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findByChildAndTypes(
        int $childId,
        array $eventTypeIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        \DateTimeZone $timezone,
    ): array {
        $utc = new \DateTimeZone(self::UTC);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT occurred_at, event_type_id
             FROM events
             WHERE child_id = :childId
               AND event_type_id IN (:types)
               AND occurred_at >= :from
               AND occurred_at <= :to
             ORDER BY occurred_at',
            [
                'childId' => $childId,
                'types' => $eventTypeIds,
                'from' => $from->setTimezone($utc)->format('Y-m-d H:i:sP'),
                'to' => $to->setTimezone($utc)->format('Y-m-d H:i:sP'),
            ],
            [
                'types' => ArrayParameterType::INTEGER,
            ],
        );

        return array_map(
            static fn (array $row): Event => new Event(
                (new \DateTimeImmutable($row['occurred_at']))->setTimezone($timezone),
                (int) $row['event_type_id'],
            ),
            $rows,
        );
    }

    public function findLastEventPerType(int $childId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT * FROM (
             SELECT DISTINCT ON (e.event_type_id)
                    e.id, e.child_id, e.event_type_id, et.name, e.occurred_at, e.volume, e.description
             FROM events e
             JOIN event_types et ON et.id = e.event_type_id
             WHERE e.child_id = :childId
             ORDER BY e.event_type_id, e.occurred_at DESC
         ) last_events
         ORDER BY occurred_at DESC',
            ['childId' => $childId],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function listByChild(int $childId, int $limit, int $offset): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT e.id, e.child_id, e.event_type_id, et.name, e.occurred_at, e.volume, e.description
             FROM events e
             JOIN event_types et ON et.id = e.event_type_id
             WHERE e.child_id = :childId
             ORDER BY e.occurred_at DESC, e.id DESC
             LIMIT :limit OFFSET :offset',
            ['childId' => $childId, 'limit' => $limit, 'offset' => $offset],
            ['limit' => ParameterType::INTEGER, 'offset' => ParameterType::INTEGER],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function findById(int $id): ?EventDetails
    {
        $row = $this->connection->fetchAssociative(
            'SELECT e.id, e.child_id, e.event_type_id, et.name, e.occurred_at, e.volume, e.description
             FROM events e
             JOIN event_types et ON et.id = e.event_type_id
             WHERE e.id = :id',
            ['id' => $id],
        );

        return false === $row ? null : $this->hydrate($row);
    }

    public function findDetailsByChildAndTypes(
        int $childId,
        array $eventTypeIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        \DateTimeZone $timezone,
    ): array {
        if ([] === $eventTypeIds) {
            return [];
        }

        $utc = new \DateTimeZone(self::UTC);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT e.id, e.child_id, e.event_type_id, et.name, e.occurred_at, e.volume, e.description
             FROM events e
             JOIN event_types et ON et.id = e.event_type_id
             WHERE e.child_id = :childId
               AND e.event_type_id IN (:types)
               AND e.occurred_at >= :from
               AND e.occurred_at < :to
             ORDER BY e.occurred_at, e.id',
            [
                'childId' => $childId,
                'types' => $eventTypeIds,
                'from' => $from->setTimezone($utc)->format('Y-m-d H:i:sP'),
                'to' => $to->setTimezone($utc)->format('Y-m-d H:i:sP'),
            ],
            [
                'types' => ArrayParameterType::INTEGER,
            ],
        );

        return array_map(
            fn (array $row): EventDetails => $this->hydrate($row, $timezone),
            $rows,
        );
    }

    public function findTopVolumes(
        int $childId,
        array $eventTypeIds,
        \DateTimeImmutable $since,
        int $limit,
    ): array {
        if ([] === $eventTypeIds) {
            return [];
        }

        // Оконная функция считается после группировки, поэтому COUNT(*) внутри
        // ROW_NUMBER() — это частота конкретного объёма. Тайбрейк по свежести
        // делает порядок детерминированным при равной частоте.
        $rows = $this->connection->fetchAllAssociative(
            'SELECT event_type_id, volume
             FROM (
                 SELECT event_type_id,
                        volume,
                        ROW_NUMBER() OVER (
                            PARTITION BY event_type_id
                            ORDER BY COUNT(*) DESC, MAX(occurred_at) DESC
                        ) AS rn
                 FROM events
                 WHERE child_id = :childId
                   AND event_type_id IN (:types)
                   AND volume IS NOT NULL
                   AND occurred_at >= :since
                 GROUP BY event_type_id, volume
             ) ranked
             WHERE rn <= :limit
             ORDER BY event_type_id, rn',
            [
                'childId' => $childId,
                'types' => $eventTypeIds,
                'since' => $since->setTimezone(new \DateTimeZone(self::UTC))->format('Y-m-d H:i:sP'),
                'limit' => $limit,
            ],
            [
                'types' => ArrayParameterType::INTEGER,
                'limit' => ParameterType::INTEGER,
            ],
        );

        $volumes = [];

        foreach ($rows as $row) {
            $volumes[(int) $row['event_type_id']][] = (int) $row['volume'];
        }

        return $volumes;
    }

    public function create(EventDraft $draft): int
    {
        return (int) $this->connection->fetchOne(
            'INSERT INTO events (child_id, event_type_id, occurred_at, volume, description)
             VALUES (:childId, :eventTypeId, :occurredAt, :volume, :description)
             RETURNING id',
            [
                'childId' => $draft->childId,
                'eventTypeId' => $draft->eventTypeId,
                'occurredAt' => $this->toUtcString($draft->occurredAt),
                'volume' => $draft->volume,
                'description' => $draft->description,
            ],
            ['volume' => ParameterType::INTEGER],
        );
    }

    public function update(int $id, EventPatch $patch): void
    {
        $assignments = [];
        $params = ['id' => $id];
        $types = [];

        if (null !== $patch->occurredAt) {
            $assignments[] = 'occurred_at = :occurredAt';
            $params['occurredAt'] = $this->toUtcString($patch->occurredAt);
        }

        if (null !== $patch->volume) {
            $assignments[] = 'volume = :volume';
            $params['volume'] = $patch->volume;
            $types['volume'] = ParameterType::INTEGER;
        }

        if (null !== $patch->description) {
            $assignments[] = 'description = :description';
            $params['description'] = $patch->description;
        }

        if ([] === $assignments) {
            return;
        }

        $this->connection->executeStatement(
            sprintf('UPDATE events SET %s WHERE id = :id', implode(', ', $assignments)),
            $params,
            $types,
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row, ?\DateTimeZone $timezone = null): EventDetails
    {
        return new EventDetails(
            id: (int) $row['id'],
            childId: (int) $row['child_id'],
            eventTypeId: (int) $row['event_type_id'],
            name: $row['name'],
            occurredAt: (new \DateTimeImmutable($row['occurred_at']))
                ->setTimezone($timezone ?? new \DateTimeZone(self::UTC)),
            volume: null === $row['volume'] ? null : (int) $row['volume'],
            description: $row['description'],
        );
    }

    private function toUtcString(\DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new \DateTimeZone(self::UTC))->format('Y-m-d H:i:sP');
    }
}
