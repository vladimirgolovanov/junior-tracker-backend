<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Event\ValueObject\EventType;

final class EventTypeMapper
{
    private const RANGE_END_FORMAT = 'range_end';
    private const HIDDEN_FROM_FILTERS_NAMES = ['sleep_start', 'sleep_end'];

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
        // show_in_filters / volume_input / describe_input — не колонки, а производные
        // (volume/describe — от format; show_in_filters — от format и name, см. showInFilters()).
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
            'show_in_filters' => $this->showInFilters($eventType),
            'volume_input' => 'metric' === $eventType->format,
            'describe_input' => 'described' === $eventType->format,
            'show_in_last_events' => $eventType->showInLastEvents,
            'show_in_quick_actions' => $eventType->showInQuickActions,
        ];
    }

    private function showInFilters(EventType $eventType): bool
    {
        // Only the range start belongs in filters; the paired *_end type never does.
        if (self::RANGE_END_FORMAT === $eventType->format) {
            return false;
        }

        // Sleep is a special range pair (identified by name across the codebase,
        // e.g. ChildStatusBuilder) and must stay out of filters entirely.
        return !in_array($eventType->name, self::HIDDEN_FROM_FILTERS_NAMES, true);
    }
}
