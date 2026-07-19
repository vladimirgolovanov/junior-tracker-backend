<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

final readonly class Event
{
    public function __construct(
        public \DateTimeImmutable $occurredAt,
        public int $eventTypeId,
    ) {
    }
}
