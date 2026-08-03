<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;

final readonly class ListEventTypes
{
    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private EventTypeReadRepositoryInterface $eventTypes,
    ) {
    }

    /**
     * @return \App\Domain\Event\ValueObject\EventType[]
     *
     * @throws AccessDenied пользователь не связан с этим ребёнком
     */
    public function __invoke(int $userId, int $childId): array
    {
        if (!$this->childAccess->userHasAccessToChild($userId, $childId)) {
            throw AccessDenied::toChild($childId);
        }

        return $this->eventTypes->listByChild($childId);
    }
}
