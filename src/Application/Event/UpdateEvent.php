<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Event\Exception\EventNotFound;
use App\Domain\Event\Repository\EventReadRepositoryInterface;
use App\Domain\Event\Repository\EventWriteRepositoryInterface;
use App\Domain\Event\ValueObject\EventDetails;
use App\Domain\Event\ValueObject\EventPatch;

final readonly class UpdateEvent
{
    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private EventReadRepositoryInterface $eventsRead,
        private EventWriteRepositoryInterface $events,
    ) {
    }

    /**
     * @throws EventNotFound события с таким id нет
     * @throws AccessDenied  пользователь не связан с ребёнком этого события
     */
    public function __invoke(int $userId, int $eventId, EventPatch $patch): EventDetails
    {
        $event = $this->eventsRead->findById($eventId);

        if (null === $event) {
            throw EventNotFound::byId($eventId);
        }

        if (!$this->childAccess->userHasAccessToChild($userId, $event->childId)) {
            throw AccessDenied::toChild($event->childId);
        }

        if ($patch->isEmpty()) {
            return $event;
        }

        $this->events->update($eventId, $patch);

        return $this->eventsRead->findById($eventId) ?? throw EventNotFound::byId($eventId);
    }
}
