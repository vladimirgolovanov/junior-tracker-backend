<?php

declare(strict_types=1);

namespace App\Domain\Sleep\Repository;

use App\Domain\Sleep\ValueObject\SleepPrediction;

interface SleepPredictRepositoryInterface
{
    /**
     * Prediction made for the given sleep event.
     *
     * @param \DateTimeZone $timezone the child's timezone, segments are hydrated into it
     *
     * @return SleepPrediction[] empty when there is no usable prediction
     */
    public function findByChildAndOccurredAt(
        int $childId,
        \DateTimeImmutable $occurredAt,
        \DateTimeZone $timezone,
    ): array;
}
