<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Event\ValueObject\EventDetails;

final class EventMapper
{
    /**
     * @param EventDetails[] $events
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(array $events): array
    {
        return array_map($this->one(...), $events);
    }

    /**
     * Форма совпадает с last_events в /api/v2/status плюс id,
     * чтобы фронт переиспользовал один рендер события.
     *
     * @return array<string, mixed>
     */
    public function one(EventDetails $event): array
    {
        return [
            'id' => $event->id,
            'event_type_id' => $event->eventTypeId,
            'name' => $event->name,
            'occurred_at' => $event->occurredAt->format(\DateTimeInterface::ATOM),
            'volume' => $event->volume,
            'description' => $event->description,
        ];
    }
}
