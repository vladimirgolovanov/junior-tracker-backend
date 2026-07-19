<?php

declare(strict_types=1);

namespace App\Domain\Sleep\Service;

use App\Domain\Event\ValueObject\Event;
use App\Domain\Event\ValueObject\RangeEventType;
use App\Domain\Sleep\ValueObject\SleepSchedule;

final class CycleDayEventsIsolator
{
    /**
     * @param Event[] $events
     *
     * @return Event[]
     */
    public function isolate(
        array              $events,
        \DateTimeImmutable $date,
        RangeEventType     $eventTypes,
        SleepSchedule      $schedule,
    ): array {
        $dayStart = $schedule->dayStartAt($date);

        $isolated = [];
        $earlyWakeUp = null;
        $overnightSleepStarted = false;

        foreach ($events as $event) {
            $isTargetDate = $event->occurredAt->format('Y-m-d') === $date->format('Y-m-d');
            $isSleepStart = $event->eventTypeId === $eventTypes->startId;
            $isSleepEnd = $event->eventTypeId === $eventTypes->endId;

            if (!$isTargetDate) {
                if ($overnightSleepStarted && $isSleepEnd) {
                    $isolated[] = $event;

                    break;
                }

                continue;
            }

            if ($event->occurredAt < $dayStart) {
                if ($isSleepEnd) {
                    $earlyWakeUp = $event;
                }

                continue;
            }

            if ($isSleepStart && null !== $earlyWakeUp && [] === $isolated) {
                $isolated[] = $earlyWakeUp;
                $earlyWakeUp = null;
            }

            $isolated[] = $event;

            if ($isSleepStart) {
                $overnightSleepStarted = true;
            }
        }

        return $isolated;
    }
}
