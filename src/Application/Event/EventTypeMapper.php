<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Event\ValueObject\EventType;

final class EventTypeMapper
{
    private const RANGE_FORMATS = ['range', 'range_end'];

    /**
     * @param EventType[] $eventTypes
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(array $eventTypes): array
    {
        return array_map($this->one(...), $eventTypes);
    }

    /**
     * @return array<string, mixed>
     */
    public function one(EventType $eventType): array
    {
        // show_in_filters / volume_input / describe_input — не колонки,
        // а производные от format (правило перенесено из FastAPI).
        // Рядом с ними едут show_in_last_events / show_in_quick_actions —
        // это, наоборот, настоящие пользовательские настройки из БД.
        return [
            'id' => $eventType->id,
            'format' => $eventType->format,
            'color' => $eventType->color,
            'parent_id' => $eventType->parentId,
            'name' => $eventType->name,
            'keywords' => $eventType->keywords,
            'child_id' => $eventType->childId,
            'show_in_filters' => !in_array($eventType->format, self::RANGE_FORMATS, true),
            'volume_input' => 'metric' === $eventType->format,
            'describe_input' => 'described' === $eventType->format,
            'show_in_last_events' => $eventType->showInLastEvents,
            'show_in_quick_actions' => $eventType->showInQuickActions,
        ];
    }
}
