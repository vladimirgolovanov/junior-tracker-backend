<?php

declare(strict_types=1);

namespace App\Domain\Sleep\Service;

use App\Domain\Sleep\Enum\DayPart;
use App\Domain\Sleep\Enum\SleepState;
use App\Domain\Sleep\ValueObject\SleepSchedule;

final class DayPartResolver
{
    public function resolve(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        \DateTimeImmutable $date,
        SleepState $state,
        SleepSchedule $schedule,
    ): DayPart {
        $dayStart = $schedule->dayStartAt($date);
        $dayEnd = $schedule->dayEndAt($date);

        $startsInDay = $start >= $dayStart && $start <= $dayEnd;
        $endsInDay = $end >= $dayStart && $end <= $dayEnd;

        // A cycle spans at most into the following calendar day (evening bedtime -> next morning),
        // so a segment that falls within the next day's window is a daytime one, not night:
        // an awake segment that starts there is the morning rise, and a sleep fully contained
        // there is a morning nap.
        $nextDayStart = $schedule->dayStartAt($date->modify('+1 day'));
        $nextDayEnd = $schedule->dayEndAt($date->modify('+1 day'));
        $startsNextMorning = $start >= $nextDayStart && $start <= $nextDayEnd;
        $endsNextMorning = $end >= $nextDayStart && $end <= $nextDayEnd;

        return match ($state) {
            SleepState::Asleep => ($startsInDay && $endsInDay) || ($startsNextMorning && $endsNextMorning)
                ? DayPart::Day
                : DayPart::Night,
            SleepState::Awake => $startsInDay || $endsInDay || $startsNextMorning ? DayPart::Day : DayPart::Night,
        };
    }
}
