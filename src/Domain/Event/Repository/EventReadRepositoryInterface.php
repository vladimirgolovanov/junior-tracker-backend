<?php

declare(strict_types=1);

namespace App\Domain\Event\Repository;

use App\Domain\Event\ValueObject\EventDetails;

interface EventReadRepositoryInterface
{
    /**
     * Последнее событие каждого типа этого ребёнка.
     *
     * @return EventDetails[] one per event_type_id, occurredAt in $timezone
     */
    public function findLastEventPerType(int $childId, \DateTimeZone $timezone): array;

    /**
     * Лента событий ребёнка, от свежих к старым.
     *
     * @return EventDetails[]
     */
    public function listByChild(int $childId, int $limit, int $offset): array;

    public function findById(int $id): ?EventDetails;

    /**
     * All of a child's events across every type, oldest first, optionally
     * bounded by an occurred_at range [from, to). Unlike
     * findDetailsByChildAndTypes the type list is not required and the range is
     * optional — the shape the data export needs.
     *
     * @return EventDetails[] ordered by occurredAt (in $timezone)
     */
    public function findAllByChild(
        int $childId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        \DateTimeZone $timezone,
    ): array;

    /**
     * Полные события заданных типов за период — как findByChildAndTypes,
     * но с volume/description, которые нужны графику.
     *
     * @param int[] $eventTypeIds
     *
     * @return EventDetails[] упорядоченные по occurredAt (в таймзоне $timezone)
     */
    public function findDetailsByChildAndTypes(
        int $childId,
        array $eventTypeIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        \DateTimeZone $timezone,
    ): array;

    /**
     * Самые частые объёмы по каждому типу — подсказки для поля ввода.
     *
     * @param int[] $eventTypeIds
     *
     * @return array<int, list<int>> event_type_id => объёмы, самый частый первым
     */
    public function findTopVolumes(
        int $childId,
        array $eventTypeIds,
        \DateTimeImmutable $since,
        int $limit,
    ): array;
}
