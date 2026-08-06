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
    public function __invoke(int $userId, EventDraft $draft): EventDetails
    {
        if (!$this->childAccess->userHasAccessToChild($userId, $draft->childId)) {
            throw AccessDenied::toChild($draft->childId);
        }

        // Тип события принадлежит конкретному ребёнку, поэтому чужой event_type_id
        // при разрешённом child_id создал бы событие-химеру.
        $eventType = $this->eventTypes->findById($draft->eventTypeId);

        if (null === $eventType || $eventType->childId !== $draft->childId) {
            throw EventTypeNotFound::inChild($draft->childId, $draft->eventTypeId);
        }

        $id = $this->events->create($draft);

        return $this->eventsRead->findById($id) ?? throw EventNotFound::byId($id);
    }
}
