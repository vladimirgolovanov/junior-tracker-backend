<?php

declare(strict_types=1);

namespace App\Application\Sleep;

use App\Domain\Sleep\ValueObject\DaySummary;
use App\Domain\Sleep\ValueObject\SleepSegment;

final class DaySummaryMapper
{
    public function toArray(DaySummary $summary): array
    {
        return [
            'segments' => array_map(
                $this->segmentToArray(...),
                array_reverse($summary->segments),
            ),
            'bedtime' => $summary->bedtime?->format(\DateTimeInterface::ATOM),
            'morningAwakeTime' => $summary->morningAwakeTime?->format(\DateTimeInterface::ATOM),
            'totalSleepMinutes' => $summary->totalSleepMinutes,
            'daySleepMinutes' => $summary->daySleepMinutes,
            'nightSleepMinutes' => $summary->nightSleepMinutes,
            'totalAwakeMinutes' => $summary->totalAwakeMinutes,
            'dayAwakeMinutes' => $summary->dayAwakeMinutes,
            'nightAwakeMinutes' => $summary->nightAwakeMinutes,
            'currentSleepMinutes' => $summary->currentSleepMinutes,
            'currentAwakeMinutes' => $summary->currentAwakeMinutes,
            'isCurrentlyAsleep' => $summary->isCurrentlyAsleep,
            'cycleLengthMinutes' => $summary->cycleLengthMinutes,
        ];
    }

    private function segmentToArray(SleepSegment $segment): array
    {
        return [
            'start' => $segment->start->format(\DateTimeInterface::ATOM),
            'end' => $segment->end->format(\DateTimeInterface::ATOM),
            'state' => $segment->state->value,
            'dayPart' => $segment->dayPart->value,
            'napNumber' => $segment->napNumber,
            'isCurrent' => $segment->isCurrent,
            'minutes' => intdiv($segment->durationInSeconds(), 60),
        ];
    }
}
