<?php

declare(strict_types=1);

namespace App\Application\Sleep;

use App\Domain\Sleep\ValueObject\DaySummary;
use App\Domain\Sleep\ValueObject\SleepSegment;

final class DaySummaryMapper
{
    public const DAY_KEYS = ['today', 'yesterday', 'day_before_yesterday'];

    /**
     * @param DaySummary[] $summaries хронологически, от раннего к позднему
     */
    public function toKeyedArray(array $summaries): array
    {
        $result = [];

        foreach (array_reverse($summaries) as $i => $summary) {
            $result[self::DAY_KEYS[$i]] = $this->toArray($summary);
        }

        return $result;
    }

    public function toArray(DaySummary $summary): array
    {
        return [
            'segments' => array_map(
                $this->segmentToArray(...),
                array_reverse($summary->segments),
            ),
            'bedtime' => $summary->bedtime?->format("H:i"),
            'morning_awake_time' => $summary->morningAwakeTime?->format("H:i"),
            'total_sleep_minutes' => $summary->totalSleepMinutes,
            'day_sleep_minutes' => $summary->daySleepMinutes,
            'night_sleep_minutes' => $summary->nightSleepMinutes,
            'total_awake_minutes' => $summary->totalAwakeMinutes,
            'day_awake_minutes' => $summary->dayAwakeMinutes,
            'night_awake_minutes' => $summary->nightAwakeMinutes,
            'current_sleep_minutes' => $summary->currentSleepMinutes,
            'current_awake_minutes' => $summary->currentAwakeMinutes,
            'is_currently_asleep' => $summary->isCurrentlyAsleep,
            'cycle_length_minutes' => $summary->cycleLengthMinutes,
        ];
    }

    private function segmentToArray(SleepSegment $segment): array
    {
        return [
            'start' => $segment->start->format("H:i"),
            'end' => $segment->end->format("H:i"),
            'state' => $segment->state->value,
            'day_part' => $segment->dayPart->value,
            'nap_number' => $segment->napNumber,
            'is_current' => $segment->isCurrent,
            'minutes' => intdiv($segment->durationInSeconds(), 60),
        ];
    }
}
