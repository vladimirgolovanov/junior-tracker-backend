<?php

declare(strict_types=1);

namespace App\Application\Sleep;

use App\Domain\Sleep\ValueObject\SleepPrediction;

final class SleepPredictionMapper
{
    /**
     * @param SleepPrediction[] $predictions
     *
     * @return array<string, mixed>
     */
    public function toArray(array $predictions): array
    {
        return ['predictions' => array_map($this->one(...), $predictions)];
    }

    /**
     * @return array<string, mixed>
     */
    private function one(SleepPrediction $prediction): array
    {
        return [
            'time' => $prediction->minutes,
            // Naive local time: the timezone has already been applied on hydration.
            'end_dt' => $prediction->endAt->format('Y-m-d H:i:s'),
            'start_dt' => $prediction->startAt->format('Y-m-d H:i:s'),
            'segment_type' => $prediction->segmentType,
        ];
    }
}
