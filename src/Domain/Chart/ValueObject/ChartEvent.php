<?php

declare(strict_types=1);

namespace App\Domain\Chart\ValueObject;

use App\Domain\Event\ValueObject\EventDetails;

/**
 * An additional-data marker on the chart.
 */
final readonly class ChartEvent
{
    /**
     * @param EventDetails $event           for a range type this is the start event
     * @param bool         $ranged          the type has a paired end, so the marker carries a duration
     * @param int|null     $durationMinutes null for a range that has not ended yet
     */
    public function __construct(
        public EventDetails $event,
        public bool $ranged,
        public ?int $durationMinutes,
    ) {
    }

    public static function point(EventDetails $event): self
    {
        return new self($event, false, null);
    }

    public static function ranged(EventDetails $start, ?int $durationMinutes): self
    {
        return new self($start, true, $durationMinutes);
    }
}
