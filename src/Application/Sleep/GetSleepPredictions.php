<?php

declare(strict_types=1);

namespace App\Application\Sleep;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Child\Repository\ChildRepositoryInterface;
use App\Domain\Event\Exception\EventTypeNotFound;
use App\Domain\Event\Repository\EventReadRepositoryInterface;
use App\Domain\Event\Repository\EventTypeRepositoryInterface;
use App\Domain\Event\ValueObject\EventDetails;
use App\Domain\Event\ValueObject\RangeEventType;
use App\Domain\Sleep\Repository\SleepPredictRepositoryInterface;
use App\Domain\Sleep\ValueObject\SleepPrediction;

final readonly class GetSleepPredictions
{
    private const SLEEP_START = 'sleep_start';

    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private ChildRepositoryInterface $children,
        private EventTypeRepositoryInterface $eventTypes,
        private EventReadRepositoryInterface $events,
        private SleepPredictRepositoryInterface $predicts,
    ) {
    }

    /**
     * @return SleepPrediction[] empty while there is nothing to predict from yet
     *
     * @throws AccessDenied the user is not linked to this child
     */
    public function handle(int $userId, int $childId): array
    {
        if (!$this->childAccess->userHasAccessToChild($userId, $childId)) {
            throw AccessDenied::toChild($childId);
        }

        try {
            $sleepType = $this->eventTypes->findRangeType($childId, self::SLEEP_START);
        } catch (EventTypeNotFound) {
            // The child has no sleep types, so no prediction could have been made.
            return [];
        }

        $timezone = $this->children->findTimezone($childId);
        $lastSleepEvent = $this->lastSleepEvent($childId, $sleepType, $timezone);

        if (null === $lastSleepEvent) {
            return [];
        }

        return $this->predicts->findByChildAndOccurredAt(
            $childId,
            $lastSleepEvent->occurredAt,
            $timezone,
        );
    }

    /**
     * The prediction is recomputed on every sleep event, so the row is keyed by the
     * moment of the latest sleep_start or sleep_end.
     */
    private function lastSleepEvent(
        int $childId,
        RangeEventType $sleepType,
        \DateTimeZone $timezone,
    ): ?EventDetails {
        // findLastEventPerType already returns one freshest event per type,
        // ordered from the latest to the earliest.
        foreach ($this->events->findLastEventPerType($childId, $timezone) as $event) {
            if (in_array($event->eventTypeId, [$sleepType->startId, $sleepType->endId], true)) {
                return $event;
            }
        }

        return null;
    }
}
