<?php

declare(strict_types=1);

namespace App\Domain\Sleep\Service;

use App\Domain\Event\ValueObject\Event;
use App\Domain\Event\ValueObject\RangeEventType;
use App\Domain\Sleep\Enum\DayPart;
use App\Domain\Sleep\Enum\SleepState;
use App\Domain\Sleep\ValueObject\DaySummary;
use App\Domain\Sleep\ValueObject\SleepSchedule;
use App\Domain\Sleep\ValueObject\SleepSegment;

final class DaySummaryBuilder
{
    public function __construct(
        private readonly DayPartResolver $dayPartResolver,
    ) {
    }

    /** @param Event[] $events */
    public function buildDaySummary(
        array               $events,
        RangeEventType      $eventTypes,
        SleepSchedule       $schedule,
        ?\DateTimeImmutable $currentTime = null,
    ): DaySummary {
        $segments = $this->buildSegments($events, $eventTypes, $schedule, $currentTime);

        return DaySummary::fromSegments($segments);
    }

    /**
     * @param Event[] $events
     *
     * @return SleepSegment[]
     */
    private function buildSegments(
        array               $events,
        RangeEventType      $eventTypes,
        SleepSchedule       $schedule,
        ?\DateTimeImmutable $currentTime,
    ): array {
        $segments = [];
        $napNumber = 0;
        $count = count($events);
        $cycleDate = $events[0]->occurredAt;

        for ($i = 0; $i < $count; ++$i) {
            $event = $events[$i];
            $isLast = $i === $count - 1;

            $end = $isLast ? $currentTime : $events[$i + 1]->occurredAt;

            if (null === $end) {
                break;
            }

            $state = $event->eventTypeId === $eventTypes->startId
                ? SleepState::Asleep
                : SleepState::Awake;

            $dayPart = $this->dayPartResolver->resolve($event->occurredAt, $end, $cycleDate, $state, $schedule);

            $isCurrent = $isLast && null !== $currentTime;

            $nap = null;
            if (SleepState::Asleep === $state && DayPart::Day === $dayPart && !$isCurrent) {
                $nap = ++$napNumber;
            }

            $segments[] = new SleepSegment(
                start: $event->occurredAt,
                end: $end,
                state: $state,
                dayPart: $dayPart,
                napNumber: $nap,
                isCurrent: $isCurrent,
            );
        }

        return $segments;
    }
}
