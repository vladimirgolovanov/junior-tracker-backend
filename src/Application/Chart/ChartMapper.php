<?php

declare(strict_types=1);

namespace App\Application\Chart;

use App\Domain\Chart\ValueObject\Chart;
use App\Domain\Chart\ValueObject\ChartEvent;
use App\Domain\Chart\ValueObject\SleepInterval;

final class ChartMapper
{
    /**
     * Naive local time: the child's timezone has already been applied upstream,
     * so the offset is intentionally not rendered.
     */
    private const DATETIME = 'Y-m-d H:i:s';

    /**
     * @return array<string, mixed>
     */
    public function toArray(Chart $chart): array
    {
        $additional = [];
        foreach ($chart->additionalData as $eventTypeId => $events) {
            $additional[(string) $eventTypeId] = array_map($this->eventToArray(...), $events);
        }

        return [
            'sleep_data' => array_map($this->intervalToArray(...), $chart->sleepData),
            // Пустой additional_data должен остаться объектом, а не превратиться в [].
            'additional_data' => (object) $additional,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function intervalToArray(SleepInterval $interval): array
    {
        return [
            'day' => $interval->day(),
            'start' => $interval->start->format(self::DATETIME),
            'end' => $interval->end?->format(self::DATETIME),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function eventToArray(ChartEvent $marker): array
    {
        $result = [
            'id' => $marker->event->id,
            'event_type_id' => $marker->event->eventTypeId,
            'occurred_at' => $marker->event->occurredAt->format(self::DATETIME),
        ];

        // volume и description есть не у всех типов — у остальных ключа быть не должно.
        if (null !== $marker->event->volume) {
            $result['volume'] = $marker->event->volume;
        }

        if (null !== $marker->event->description) {
            $result['description'] = $marker->event->description;
        }

        // У range-типов duration есть всегда: null означает «ещё не закончилось».
        if ($marker->ranged) {
            $result['duration'] = $marker->durationMinutes;
        }

        return $result;
    }
}
