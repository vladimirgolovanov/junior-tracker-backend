<?php

declare(strict_types=1);

namespace App\Domain\Event\Repository;

use App\Domain\Event\ValueObject\EventType;

interface EventTypeReadRepositoryInterface
{
    /**
     * @return EventType[] упорядоченные по id
     */
    public function listByChild(int $childId): array;

    public function findById(int $id): ?EventType;

    public function findRangePair($parentId): ?EventType;
}
