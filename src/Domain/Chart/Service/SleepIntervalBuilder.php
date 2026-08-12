<?php

declare(strict_types=1);

namespace App\Domain\Chart\Service;

use App\Domain\Chart\ValueObject\SleepInterval;
use App\Domain\Event\ValueObject\Event;
use App\Domain\Event\ValueObject\RangeEventType;

/**
 * Turns the raw sleep_start/sleep_end stream into chart bars: paired, clipped to the
 * requested range and split at local midnight.
 *
 * Deliberately does not reuse CycleDayEventsIsolator: that one groups events into
 * "sleep days" starting at 06:00, while a chart needs an honest calendar timeline.
 */
final class SleepIntervalBuilder
{
    /**
     * @param Event[]            $events ordered by occurredAt, in the child's timezone
     * @param \DateTimeImmutable $from   local midnight of the first requested day
     * @param \DateTimeImmutable $until  local midnight after the last requested day, exclusive
     *
     * @return list<SleepInterval>
     */
    public function build(
        array $events,
        RangeEventType $sleepType,
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
    ): array {
        $intervals = [];

        foreach ($this->pair($events, $sleepType) as [$start, $end]) {
            foreach ($this->clip($start, $end, $from, $until) as $interval) {
                $intervals[] = $interval;
            }
        }

        return $intervals;
    }

    /**
     * @param Event[] $events
     *
     * @return list<array{0: \DateTimeImmutable, 1: \DateTimeImmutable|null}>
     */
    private function pair(array $events, RangeEventType $sleepType): array
    {
        $intervals = [];
        $pendingStart = null;

        foreach ($events as $event) {
            if ($event->eventTypeId === $sleepType->startId) {
                // Two starts in a row mean a missing sleep_end; the earlier start wins,
                // otherwise a lost end event would swallow the whole sleep.
                $pendingStart ??= $event->occurredAt;

                continue;
            }

            if ($event->eventTypeId === $sleepType->endId && null !== $pendingStart) {
                $intervals[] = [$pendingStart, $event->occurredAt];
                $pendingStart = null;
            }
        }

        if (null !== $pendingStart) {
            $intervals[] = [$pendingStart, null];
        }

        return $intervals;
    }

    /**
     * @return list<SleepInterval>
     */
    private function clip(
        \DateTimeImmutable $start,
        ?\DateTimeImmutable $end,
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
    ): array {
        if (null === $end) {
            // The child is still asleep: there is no end to clip against and no way to
            // tell which midnights the sleep will cross, so it stays one open bar.
            if ($start >= $until) {
                return [];
            }

            return [new SleepInterval($start < $from ? $from : $start, null)];
        }

        if ($end <= $from || $start >= $until) {
            return [];
        }

        $start = $start < $from ? $from : $start;
        $end = $end > $until ? $until : $end;

        if ($start >= $end) {
            return [];
        }

        return $this->splitAtMidnight($start, $end);
    }

    /**
     * @return list<SleepInterval>
     */
    private function splitAtMidnight(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $pieces = [];
        $cursor = $start;

        while (true) {
            // setTime + modify keeps this calendar-based, so a DST day still has exactly
            // one midnight boundary even though it is not 24 hours long.
            $nextMidnight = $cursor->setTime(0, 0)->modify('+1 day');

            if ($nextMidnight >= $end) {
                $pieces[] = new SleepInterval($cursor, $end);

                return $pieces;
            }

            $pieces[] = new SleepInterval($cursor, $nextMidnight);
            $cursor = $nextMidnight;
        }
    }
}
