<?php

declare(strict_types=1);

namespace App\Domain\Sleep\ValueObject;

use App\Domain\Sleep\Enum\DayPart;
use App\Domain\Sleep\Enum\SleepState;

final readonly class DaySummary
{
    /** @param SleepSegment[] $segments */
    private function __construct(
        public array $segments,
        public ?\DateTimeImmutable $bedtime,
        public ?\DateTimeImmutable $morningAwakeTime,
        public int $totalSleepMinutes,
        public int $daySleepMinutes,
        public int $nightSleepMinutes,
        public int $totalAwakeMinutes,
        public int $dayAwakeMinutes,
        public int $nightAwakeMinutes,
        public int $currentSleepMinutes,
        public int $currentAwakeMinutes,
        public bool $isCurrentlyAsleep,
        public int $cycleLengthMinutes,
    ) {
    }

    /** @param SleepSegment[] $segments */
    public static function fromSegments(array $segments): self
    {
        $sleep = [DayPart::Day->value => 0, DayPart::Night->value => 0];
        $awake = [DayPart::Day->value => 0, DayPart::Night->value => 0];

        $first = $segments[array_key_first($segments)] ?? null;

        $morningAwakeTime = null !== $first && SleepState::Awake === $first->state
            ? $first->start
            : null;

        $bedtime = null;
        $currentSleepSeconds = 0;
        $currentAwakeSeconds = 0;
        $isCurrentlyAsleep = false;

        foreach ($segments as $segment) {
            $seconds = $segment->durationInSeconds();
            $isAsleep = SleepState::Asleep === $segment->state;

            if ($isAsleep) {
                $sleep[$segment->dayPart->value] += $seconds;
            } else {
                $awake[$segment->dayPart->value] += $seconds;
            }

            if (null === $bedtime && $isAsleep && DayPart::Night === $segment->dayPart) {
                $bedtime = $segment->start;
            }

            if ($segment->isCurrent) {
                $isCurrentlyAsleep = $isAsleep;
                $isAsleep ? $currentSleepSeconds = $seconds : $currentAwakeSeconds = $seconds;
            }
        }

        return new self(
            segments: $segments,
            bedtime: $bedtime,
            morningAwakeTime: $morningAwakeTime,
            totalSleepMinutes: intdiv($sleep[DayPart::Day->value] + $sleep[DayPart::Night->value], 60),
            daySleepMinutes: intdiv($sleep[DayPart::Day->value], 60),
            nightSleepMinutes: intdiv($sleep[DayPart::Night->value], 60),
            totalAwakeMinutes: intdiv($awake[DayPart::Day->value] + $awake[DayPart::Night->value], 60),
            dayAwakeMinutes: intdiv($awake[DayPart::Day->value], 60),
            nightAwakeMinutes: intdiv($awake[DayPart::Night->value], 60),
            currentSleepMinutes: intdiv($currentSleepSeconds, 60),
            currentAwakeMinutes: intdiv($currentAwakeSeconds, 60),
            isCurrentlyAsleep: $isCurrentlyAsleep,
            cycleLengthMinutes: self::cycleLengthMinutes($segments),
        );
    }

    /** @param SleepSegment[] $segments */
    private static function cycleLengthMinutes(array $segments): int
    {
        $seconds = 0;

        foreach ($segments as $segment) {
            $seconds += $segment->durationInSeconds();
        }

        return intdiv($seconds, 60);
    }
}
