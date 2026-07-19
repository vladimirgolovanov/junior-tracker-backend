<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

final readonly class RangeEventType
{
    public function __construct(
        public int $startId,
        public int $endId,
    ) {
    }
}
