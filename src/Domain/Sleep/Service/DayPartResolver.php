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

        return match ($state) {
            SleepState::Asleep => $startsInDay && $endsInDay ? DayPart::Day : DayPart::Night,
            SleepState::Awake => $startsInDay || $endsInDay ? DayPart::Day : DayPart::Night,
        };
    }
}
