<?php

declare(strict_types=1);

namespace App\Domain\Sleep\ValueObject;

/**
 * One predicted sleep/awake segment.
 */
final readonly class SleepPrediction
{
    /**
     * @param \DateTimeImmutable $startAt     already in the child's timezone
     * @param \DateTimeImmutable $endAt       already in the child's timezone
     * @param int                $minutes     segment length as reported by the predictor
     * @param string             $segmentType kept as a plain string, not an enum: the values come
     *                                        from an external predictor and a newly introduced
     *                                        segment type must not break the endpoint
     */
    public function __construct(
        public \DateTimeImmutable $startAt,
        public \DateTimeImmutable $endAt,
        public int $minutes,
        public string $segmentType,
    ) {
    }
}
