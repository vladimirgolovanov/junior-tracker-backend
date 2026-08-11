<?php

namespace App\Domain\Event\ValueObject;

class EventRangeDraft
{
    public function __construct(
        public int $childId,
        public int $eventTypeId,
        public \DateTimeImmutable $occurredAt,
        public int $rangeLength,
    ) {}
}
