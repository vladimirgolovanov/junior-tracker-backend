<?php

declare(strict_types=1);

namespace App\Domain\Chart\ValueObject;

/**
 * One sleep bar on the chart, never crossing local midnight.
 */
final readonly class SleepInterval
{
    /**
     * @param \DateTimeImmutable  $start in the child's timezone
     * @param \DateTimeImmutable|null $end null while the child is still asleep
     */
    public function __construct(
        public \DateTimeImmutable $start,
        public ?\DateTimeImmutable $end,
    ) {
    }

    /**
     * The chart row this bar belongs to. Intervals are split at midnight, so the
     * start always names the day.
     */
    public function day(): string
    {
        return $this->start->format('Y-m-d');
    }
}
