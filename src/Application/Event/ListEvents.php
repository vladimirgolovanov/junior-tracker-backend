<?php

declare(strict_types=1);

namespace App\Application\Event;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Event\Repository\EventReadRepositoryInterface;

final readonly class ListEvents
{
    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private EventReadRepositoryInterface $events,
    ) {
    }

    /**
     * @return \App\Domain\Event\ValueObject\EventDetails[]
     *
     * @throws AccessDenied пользователь не связан с этим ребёнком
     */
    public function __invoke(int $userId, int $childId, int $limit, int $offset): array
    {
        if (!$this->childAccess->userHasAccessToChild($userId, $childId)) {
            throw AccessDenied::toChild($childId);
        }

        return $this->events->listByChild($childId, $limit, $offset);
    }
}
