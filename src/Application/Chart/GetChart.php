<?php

declare(strict_types=1);

namespace App\Application\Chart;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Chart\Service\AdditionalDataBuilder;
use App\Domain\Chart\Service\SleepIntervalBuilder;
use App\Domain\Chart\ValueObject\Chart;
use App\Domain\Chart\ValueObject\ChartEvent;
use App\Domain\Chart\ValueObject\SleepInterval;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Child\Repository\ChildRepositoryInterface;
use App\Domain\Event\Exception\EventTypeNotFound;
use App\Domain\Event\Repository\EventReadRepositoryInterface;
use App\Domain\Event\Repository\EventRepositoryInterface;
use App\Domain\Event\Repository\EventTypeReadRepositoryInterface;
use App\Domain\Event\Repository\EventTypeRepositoryInterface;
use App\Domain\Event\ValueObject\EventType;

final readonly class GetChart
{
    private const SLEEP_START = 'sleep_start';

    public function __construct(
        private ChildAccessRepositoryInterface $childAccess,
        private ChildRepositoryInterface $children,
        private EventTypeRepositoryInterface $eventTypes,
        private EventTypeReadRepositoryInterface $eventTypesRead,
        private EventRepositoryInterface $events,
        private EventReadRepositoryInterface $eventDetails,
        private SleepIntervalBuilder $sleepIntervalBuilder,
        private AdditionalDataBuilder $additionalDataBuilder,
    ) {
    }

    /**
     * @param list<int> $additionalDataIds типы для additional_data; чужие ребёнку отбрасываются
     *
     * @throws AccessDenied пользователь не связан с этим ребёнком
     */
    public function handle(
        int $userId,
        int $childId,
        \DateTimeImmutable $dateFrom,
        \DateTimeImmutable $dateTo,
        array $additionalDataIds,
    ): Chart {
        if (!$this->childAccess->userHasAccessToChild($userId, $childId)) {
            throw AccessDenied::toChild($childId);
        }

        $timezone = $this->children->findTimezone($childId);

        // Границы графика — календарные сутки ребёнка, а не момент запроса.
        $from = new \DateTimeImmutable($dateFrom->format('Y-m-d'), $timezone);
        $until = (new \DateTimeImmutable($dateTo->format('Y-m-d'), $timezone))->modify('+1 day');

        // День запаса с каждой стороны: сон и range-событие могут начаться до диапазона
        // и закончиться внутри него — без запаса такую пару не собрать.
        $windowFrom = $from->modify('-1 day');
        $windowUntil = $until->modify('+1 day');

        return new Chart(
            sleepData: $this->sleepData($childId, $timezone, $from, $until, $windowFrom, $windowUntil),
            additionalData: $this->markers(
                $childId,
                $additionalDataIds,
                $timezone,
                $from,
                $until,
                $windowFrom,
                $windowUntil,
            ),
        );
    }

    /**
     * @return list<SleepInterval>
     */
    private function sleepData(
        int $childId,
        \DateTimeZone $timezone,
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
        \DateTimeImmutable $windowFrom,
        \DateTimeImmutable $windowUntil,
    ): array {
        try {
            $sleepType = $this->eventTypes->findRangeType($childId, self::SLEEP_START);
        } catch (EventTypeNotFound) {
            // У ребёнка нет типов сна — рисовать нечего, но график остаётся валидным.
            return [];
        }

        $events = $this->events->findByChildAndTypes(
            $childId,
            [$sleepType->startId, $sleepType->endId],
            $windowFrom,
            $windowUntil,
            $timezone,
        );

        return $this->sleepIntervalBuilder->build($events, $sleepType, $from, $until);
    }

    /**
     * @param list<int> $requestedIds
     *
     * @return array<int, list<ChartEvent>>
     */
    private function markers(
        int $childId,
        array $requestedIds,
        \DateTimeZone $timezone,
        \DateTimeImmutable $from,
        \DateTimeImmutable $until,
        \DateTimeImmutable $windowFrom,
        \DateTimeImmutable $windowUntil,
    ): array {
        $childTypes = $this->eventTypesRead->listByChild($childId);

        // Чужие id молча отбрасываем: иначе по подобранному id можно было бы вытащить
        // события другого ребёнка.
        $ownIds = array_map(static fn (EventType $type): int => $type->id, $childTypes);
        $requestedIds = array_values(array_intersect(array_unique($requestedIds), $ownIds));

        if ([] === $requestedIds) {
            return [];
        }

        $pairIndex = [];
        foreach ($childTypes as $type) {
            if (null !== $type->parentId && in_array($type->parentId, $requestedIds, true)) {
                $pairIndex[$type->parentId] = $type->id;
            }
        }

        $events = $this->eventDetails->findDetailsByChildAndTypes(
            $childId,
            array_values(array_unique([...$requestedIds, ...array_values($pairIndex)])),
            $windowFrom,
            $windowUntil,
            $timezone,
        );

        return $this->additionalDataBuilder->build($events, $requestedIds, $pairIndex, $from, $until);
    }
}
