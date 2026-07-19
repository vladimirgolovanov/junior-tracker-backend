<?php

declare(strict_types=1);

namespace App\Domain\Event\Repository;

use App\Domain\Event\ValueObject\Event;

interface EventRepositoryInterface
{
    /**
     * @param int[] $eventTypeIds
     *
     * @return Event[] упорядоченные по occurredAt
     */
    /**
     * @param int[] $eventTypeIds
     *
     * @return Event[] упорядоченные по occurredAt (в таймзоне $timezone)
     */
    public function findByChildAndTypes(
        int $childId,
        array $eventTypeIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        \DateTimeZone $timezone,
    ): array;
}
