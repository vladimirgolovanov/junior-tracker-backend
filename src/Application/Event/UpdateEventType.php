<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Event\Exception\EventTypeNotFound;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;
use App\Domain\Event\Repository\EventTypeWriteRepositoryInterface;
use App\Domain\Event\ValueObject\EventType;
use App\Domain\Event\ValueObject\EventTypePatch;

final readonly class UpdateEventType
{
    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private EventTypeReadRepositoryInterface $eventTypesRead,
        private EventTypeWriteRepositoryInterface $eventTypes,
    ) {
    }

    /**
     * @throws EventTypeNotFound типа с таким id нет
     * @throws AccessDenied      пользователь не связан с ребёнком этого типа
     */
    public function __invoke(int $userId, int $eventTypeId, EventTypePatch $patch): EventType
    {
        $eventType = $this->eventTypesRead->findById($eventTypeId);

        if (null === $eventType) {
            throw EventTypeNotFound::byId($eventTypeId);
        }

        if (!$this->childAccess->userHasAccessToChild($userId, $eventType->childId)) {
            throw AccessDenied::toChild($eventType->childId);
        }

        if ($patch->isEmpty()) {
            return $eventType;
        }

        $this->eventTypes->update($eventTypeId, $patch);

        return $this->eventTypesRead->findById($eventTypeId) ?? throw EventTypeNotFound::byId($eventTypeId);
    }
}
