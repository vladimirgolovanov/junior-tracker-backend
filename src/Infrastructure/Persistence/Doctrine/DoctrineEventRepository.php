<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Event\Repository\EventRepositoryInterface;
use App\Domain\Event\ValueObject\Event;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class DoctrineEventRepository implements EventRepositoryInterface
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
}
