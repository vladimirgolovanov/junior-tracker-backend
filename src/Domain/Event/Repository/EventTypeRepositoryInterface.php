<?php

declare(strict_types=1);

namespace App\Domain\Event\Repository;

use App\Domain\Event\ValueObject\RangeEventType;

interface EventTypeRepositoryInterface
{
    /**
     * @param string $startName name старт-типа, напр. 'sleep_start'
     */
    public function findRangeType(int $childId, string $startName): RangeEventType;

    public function findPlainType(int $childId, string $name): int;
}
