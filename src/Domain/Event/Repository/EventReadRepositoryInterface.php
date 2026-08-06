<?php

declare(strict_types=1);

namespace App\Domain\Event\Repository;

use App\Domain\Event\ValueObject\EventDetails;

interface EventReadRepositoryInterface
{
    /**
     * Последнее событие каждого типа этого ребёнка.
     *
     * @return EventDetails[] по одному на event_type_id
     */
    public function findLastEventPerType(int $childId): array;

    /**
     * Лента событий ребёнка, от свежих к старым.
     *
     * @return EventDetails[]
     */
    public function listByChild(int $childId, int $limit, int $offset): array;

    public function findById(int $id): ?EventDetails;

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
