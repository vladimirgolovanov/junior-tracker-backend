<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Event\Exception\EventNotFound;
use App\Domain\Event\Exception\EventTypeNotFound;
use App\Domain\Event\Repository\EventReadRepositoryInterface;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;
use App\Domain\Event\Repository\EventWriteRepositoryInterface;
use App\Domain\Event\ValueObject\EventDetails;
use App\Domain\Event\ValueObject\EventDraft;
use App\Domain\Event\ValueObject\EventRangeDraft;

final readonly class CreateEvent
{
    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private EventTypeReadRepositoryInterface $eventTypes,
        private EventWriteRepositoryInterface $events,
        private EventReadRepositoryInterface $eventsRead,
    ) {
    }

    /**
     * @throws AccessDenied      пользователь не связан с этим ребёнком
     * @throws EventTypeNotFound типа нет или он принадлежит другому ребёнку
     */
    public function create(int $userId, EventDraft $draft): EventDetails
    {
        if (!$this->childAccess->userHasAccessToChild($userId, $draft->childId)) {
            throw AccessDenied::toChild($draft->childId);
        }

        $eventType = $this->eventTypes->findById($draft->eventTypeId);

        if (null === $eventType || $eventType->childId !== $draft->childId) {
            throw EventTypeNotFound::inChild($draft->childId, $draft->eventTypeId);
        }

        $id = $this->events->create($draft);

        return $this->eventsRead->findById($id) ?? throw EventNotFound::byId($id);
    }

    public function createRange(int $userId, EventRangeDraft $draft): EventDetails // ??
    {
        if (!$this->childAccess->userHasAccessToChild($userId, $draft->childId)) {
            throw AccessDenied::toChild($draft->childId);
        }

        $eventType = $this->eventTypes->findById($draft->eventTypeId);

        if (null === $eventType || $eventType->childId !== $draft->childId) {
            throw EventTypeNotFound::inChild($draft->childId, $draft->eventTypeId);
        }

        if ($eventType->format !== 'range' || !($pairEventType = $this->eventTypes->findRangePair($eventType->id))) {
            throw EventTypeNotFound::rangePair($draft->eventTypeId, $eventType->format);
        }

        $startOccurredAt = $draft->occurredAt->modify("-{$draft->rangeLength} minutes");
        $startEventDraft = new EventDraft($draft->childId, $eventType->id, $startOccurredAt);
        $startEventId = $this->events->create($startEventDraft);

        $endEventDraft = new EventDraft($draft->childId, $pairEventType->id, $draft->occurredAt);
        $endEventId = $this->events->create($endEventDraft);

        return $this->eventsRead->findById($startEventId) ?? throw EventNotFound::byId($startEventId);
    }
}
