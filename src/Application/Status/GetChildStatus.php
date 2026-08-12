<?php

declare(strict_types=1);

namespace App\Application\Status;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Child\Repository\ChildRepositoryInterface;
use App\Domain\Event\Repository\EventReadRepositoryInterface;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;
use App\Domain\Event\ValueObject\EventType;
use App\Domain\Status\Service\ChildStatusBuilder;
use App\Domain\Status\ValueObject\ChildStatus;

final readonly class GetChildStatus
{
    private const VOLUME_HINT_DAYS = 30;
    private const VOLUME_HINT_LIMIT = 2;

    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private ChildRepositoryInterface $children,
        private EventTypeReadRepositoryInterface $eventTypes,
        private EventReadRepositoryInterface $events,
        private ChildStatusBuilder $childStatusBuilder,
    ) {
    }

    /**
     * @throws AccessDenied пользователь не связан с этим ребёнком
     */
    public function handle(int $userId, int $childId, \DateTimeImmutable $now): ChildStatus
    {
        if (!$this->childAccess->userHasAccessToChild($userId, $childId)) {
            throw AccessDenied::toChild($childId);
        }

        // Which timezone to use is an application concern (it comes from the database);
        // the domain only needs the moment already expressed in the child's local time.
        $timezone = $this->children->findTimezone($childId);
        $now = $now->setTimezone($timezone);

        $eventTypes = $this->eventTypes->listByChild($childId);
        $lastEvents = $this->events->findLastEventPerType($childId, $timezone);

        $metricTypeIds = array_values(array_map(
            static fn (EventType $t): int => $t->id,
            array_filter($eventTypes, static fn (EventType $t): bool => 'metric' === $t->format),
        ));

        $volumes = [] !== $metricTypeIds
            ? $this->events->findTopVolumes(
                $childId,
                $metricTypeIds,
                $now->modify(sprintf('-%d days', self::VOLUME_HINT_DAYS)),
                self::VOLUME_HINT_LIMIT,
            )
            : [];

        return $this->childStatusBuilder->build($childId, $eventTypes, $lastEvents, $volumes, $now);
    }
}
