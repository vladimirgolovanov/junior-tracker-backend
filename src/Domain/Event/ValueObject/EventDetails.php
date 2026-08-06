<?php

declare(strict_types=1);

namespace App\Domain\Event\ValueObject;

final readonly class EventDetails
{
    public function __construct(
        public int $id,
        public int $childId,
        public int $eventTypeId,
        public string $name,
        public \DateTimeImmutable $occurredAt,
        public ?int $volume,
        public ?string $description,
    ) {
    }
}
