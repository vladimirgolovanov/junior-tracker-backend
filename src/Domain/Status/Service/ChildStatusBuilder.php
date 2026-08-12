<?php

declare(strict_types=1);

namespace App\Domain\Status\Service;

use App\Domain\Event\ValueObject\EventDetails;
use App\Domain\Event\ValueObject\EventType;
use App\Domain\Status\ValueObject\ChildStatus;
use App\Domain\Status\ValueObject\CurrentSleepState;
use App\Domain\Status\ValueObject\Action;

final readonly class ChildStatusBuilder
{
    private const string SLEEP_START = 'sleep_start';
    private const string SLEEP_END = 'sleep_end';

    /**
     * @param \DateTimeImmutable $now already in the child's timezone
     */
    public function build(
        int $childId,
        array $eventTypes,
        array $lastEvents,
        array $volumesHint,
        \DateTimeImmutable $now,
    ): ChildStatus {
        $pairIndex = $this->buildPairIndex($eventTypes);
        $lastEvents = $this->filterLastEvents($lastEvents, $pairIndex);

        return new ChildStatus(
            childId: $childId,
            sleep: $this->currentSleepState($eventTypes, $lastEvents, $now),
            lastEvents: $this->visibleLastEvents($eventTypes, $lastEvents),
            actions: $this->withVolumeHints(
                $this->buildActions($eventTypes, $lastEvents, $pairIndex),
                $volumesHint,
            ),
            currentMin: $this->minutesSinceMidnight($now),
            today: $now->format('Y-m-d'),
        );
    }

    /**
     * Read off the wall clock rather than diffing against midnight: a DST day is not
     * 1440 minutes long, and a timestamp diff would be off by the offset shift.
     */
    private function minutesSinceMidnight(\DateTimeImmutable $now): int
    {
        return (int) $now->format('G') * 60 + (int) $now->format('i');
    }

    private function filterLastEvents($lastEvents, $pairIndex): array
    {
        $seenPairs = [];
        $filteredLastEvents = [];

        foreach ($lastEvents as $event) {
            if (isset($seenPairs[$event->eventTypeId])) {
                continue;
            }
            if (isset($pairIndex[$event->eventTypeId])) {
                $seenPairs[$pairIndex[$event->eventTypeId]] = true;
            }
            $filteredLastEvents[] = $event;
        }

        return $filteredLastEvents;
    }

    private function buildPairIndex(array $eventTypes): array
    {
        $index = [];

        foreach ($eventTypes as $eventType) {
            if (null !== $eventType->parentId) {
                $index[$eventType->id] = $eventType->parentId;
                $index[$eventType->parentId] = $eventType->id;
            }
        }

        return $index;
    }

    private function buildActions(array $eventTypes, array $lastEvents, array $pairIndex): array
    {
        $eventTypesById = [];
        foreach ($eventTypes as $eventType) {
            $eventTypesById[$eventType->id] = $eventType;
        }
        $lastEventsByType = [];
        foreach ($lastEvents as $event) {
            $lastEventsByType[$event->eventTypeId] = $event;
        }

        $actions = [];

        foreach ($eventTypes as $eventType) {
            if (null !== $eventType->parentId) {
                continue;
            }

            $target = $eventType;

            if (isset($pairIndex[$eventType->id])) {
                $lastEvent = $lastEventsByType[$eventType->id] ?? $lastEventsByType[$pairIndex[$eventType->id]] ?? null;
                $target = $lastEvent?->eventTypeId === $eventType->id
                    ? $eventTypesById[$pairIndex[$eventType->id]]
                    : $eventType;
            }

            $actions[] = new Action($target->id, $this->focusFor($target), $target->showInQuickActions);
        }

        usort(
            $actions,
            static fn (Action $a, Action $b): int => $a->eventTypeId <=> $b->eventTypeId,
        );

        return $actions;
    }

    private function focusFor(EventType $eventType): ?string
    {
        return match ($eventType->format) {
            'metric' => Action::FOCUS_VOLUME,
            'described' => Action::FOCUS_DESCRIPTION,
            default => null,
        };
    }

    private function currentSleepState(array $eventTypes, array $lastEvents, \DateTimeImmutable $now): CurrentSleepState
    {
        $sleepStartType = null;
        foreach ($eventTypes as $eventType) {
            if (self::SLEEP_START === $eventType->name) {
                $sleepStartTypeId = $eventType->id;
            }
            if (self::SLEEP_END === $eventType->name) {
                $sleepEndTypeId = $eventType->id;
            }
        }

        foreach ($lastEvents as $event) {
            if ($event->eventTypeId === $sleepStartTypeId) {
                $minutes = intdiv(
                    max(0, $now->getTimestamp() - $event->occurredAt->getTimestamp()),
                    60,
                );
                return CurrentSleepState::asleep($minutes);
            }
            if ($event->eventTypeId === $sleepEndTypeId) {
                $minutes = intdiv(
                    max(0, $now->getTimestamp() - $event->occurredAt->getTimestamp()),
                    60,
                );
                return CurrentSleepState::awake($minutes);
            }
        }

        return CurrentSleepState::unknown();
    }

    private function visibleLastEvents(array $eventTypes, array $lastEvents): array
    {
        $visibleTypeIds = [];
        foreach ($eventTypes as $eventType) {
            if ($eventType->showInLastEvents) {
                $visibleTypeIds[$eventType->id] = true;
            }
        }

        $visible = array_values(array_filter(
            $lastEvents,
            static fn (EventDetails $event): bool => isset($visibleTypeIds[$event->eventTypeId]),
        ));

        usort(
            $visible,
            static fn (EventDetails $a, EventDetails $b): int => $a->eventTypeId <=> $b->eventTypeId,
        );

        return $visible;
    }

    private function withVolumeHints(array $actions, array $volumes): array
    {
        if ([] === $volumes) {
            return $actions;
        }

        return array_map(
            static fn (Action $action): Action => Action::FOCUS_VOLUME === $action->focus
                ? $action->withVolumes($volumes[$action->eventTypeId] ?? [])
                : $action,
            $actions,
        );
    }
}
