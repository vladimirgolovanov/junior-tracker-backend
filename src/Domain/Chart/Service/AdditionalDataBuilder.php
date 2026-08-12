<?php

declare(strict_types=1);

namespace App\Domain\Chart\Service;

use App\Domain\Chart\ValueObject\ChartEvent;
use App\Domain\Event\ValueObject\EventDetails;

/**
 * Groups the requested event types into chart markers, collapsing range pairs into a
 * single marker that carries its length in minutes.
 */
final class AdditionalDataBuilder
{
    /**
     * @param EventDetails[]   $events         ordered by occurredAt, in the child's timezone;
     *                                         includes the paired end types
     * @param list<int>        $requestedTypeIds
     * @param array<int, int>  $pairIndex      start type id => paired end type id
     * @param \DateTimeImmutable $from         local midnight of the first requested day
     * @param \DateTimeImmutable $until        local midnight after the last requested day, exclusive
     *
     * @return array<int, list<ChartEvent>>
     */
    public function build(
        array $events,
        array $requestedTypeIds,
        array $pairIndex,
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
    ): array {
        /** @var array<int, list<ChartEvent>> $markers */
        $markers = array_fill_keys($requestedTypeIds, []);
        $startTypeByEndType = array_flip($pairIndex);

        /** @var array<int, EventDetails> $pendingStarts */
        $pendingStarts = [];

        foreach ($events as $event) {
            $typeId = $event->eventTypeId;

            if (isset($startTypeByEndType[$typeId])) {
                $startTypeId = $startTypeByEndType[$typeId];
                $start = $pendingStarts[$startTypeId] ?? null;

                if (null !== $start) {
                    unset($pendingStarts[$startTypeId]);
                    $this->collect(
                        $markers,
                        ChartEvent::ranged($start, $this->minutesBetween($start->occurredAt, $event->occurredAt)),
                        $from,
                        $until,
                    );
                }

                continue;
            }

            if (isset($pairIndex[$typeId])) {
                // A second start before the previous one ended means a lost end event:
                // the earlier range gets no duration rather than a fabricated one.
                if (isset($pendingStarts[$typeId])) {
                    $this->collect($markers, ChartEvent::ranged($pendingStarts[$typeId], null), $from, $until);
                }

                $pendingStarts[$typeId] = $event;

                continue;
            }

            $this->collect($markers, ChartEvent::point($event), $from, $until);
        }

        // Ranges still open at the end of the window: shown, but without a duration.
        foreach ($pendingStarts as $start) {
            $this->collect($markers, ChartEvent::ranged($start, null), $from, $until);
        }

        return $markers;
    }

    /**
     * The query window is a day wider on each side so that a range starting just before
     * the range can be paired; only markers inside the requested range are kept.
     *
     * @param array<int, list<ChartEvent>> $markers
     */
    private function collect(
        array &$markers,
        ChartEvent $marker,
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
    ): void {
        $occurredAt = $marker->event->occurredAt;

        if ($occurredAt < $from || $occurredAt >= $until) {
            return;
        }

        if (!isset($markers[$marker->event->eventTypeId])) {
            return;
        }

        $markers[$marker->event->eventTypeId][] = $marker;
    }

    private function minutesBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return intdiv(max(0, $end->getTimestamp() - $start->getTimestamp()), 60);
    }
}
