<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Event\Exception\EventTypeNotFound;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;
use App\Domain\Event\Repository\EventTypeWriteRepositoryInterface;
use App\Domain\Event\Service\RangeEndNameResolver;
use App\Domain\Event\ValueObject\EventType;
use App\Domain\Event\ValueObject\EventTypeDraft;

final readonly class CreateEventType
{
    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private EventTypeWriteRepositoryInterface $eventTypes,
        private EventTypeReadRepositoryInterface $eventTypesRead,
        private RangeEndNameResolver $rangeEndName,
    ) {
    }

    /**
     * @throws AccessDenied пользователь не связан с этим ребёнком
     */
    public function __invoke(int $userId, EventTypeDraft $draft): EventType
    {
        if (!$this->childAccess->userHasAccessToChild($userId, $draft->childId)) {
            throw AccessDenied::toChild($draft->childId);
        }

        // У range-типа закрывающая половина создаётся сразу: без неё пара не работает
        // ни в графике, ни в quick_actions.
        $pairedEnd = 'range' === $draft->format
            ? $draft->pairedEnd($this->rangeEndName->resolve($draft->name))
            : null;

        $id = $this->eventTypes->create($draft, $pairedEnd);

        return $this->eventTypesRead->findById($id) ?? throw EventTypeNotFound::byId($id);
    }
}
