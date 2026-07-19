<?php

declare(strict_types=1);

namespace App\Application\Sleep;

use App\Domain\Child\Repository\ChildRepositoryInterface;
use App\Domain\Event\Repository\EventRepositoryInterface;
use App\Domain\Event\Repository\EventTypeRepositoryInterface;
use App\Domain\Sleep\Service\CycleDayEventsIsolator;
use App\Domain\Sleep\Service\DaySummaryBuilder;
use App\Domain\Sleep\ValueObject\CycleWindow;
use App\Domain\Sleep\ValueObject\DaySummary;
use App\Domain\Sleep\ValueObject\SleepSchedule;

final readonly class GetSleepSummary
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private EventTypeRepositoryInterface $eventTypeRepository,
        private ChildRepositoryInterface $childRepository,
        private CycleDayEventsIsolator $isolator,
        private DaySummaryBuilder $summaryBuilder,
    ) {
    }

    /**
     * @return DaySummary[]
     */
    public function forRange(
        int $childId,
        \DateTimeImmutable $firstDay,
        \DateTimeImmutable $lastDay,
        \DateTimeImmutable $now,
    ): array {
        $timezone = $this->childRepository->findTimezone($childId);
        $now = $now->setTimezone($timezone);

        $schedule = new SleepSchedule();
        $rangeType = $this->eventTypeRepository->findRangeType($childId, 'sleep_start');
        $window = CycleWindow::forDates($firstDay, $lastDay);

        $events = $this->eventRepository->findByChildAndTypes(
            $childId,
            [$rangeType->startId, $rangeType->endId],
            $window->from,
            $window->to,
            $timezone,
        );

        $summaries = [];
        $day = $firstDay;

        while ($day <= $lastDay) {
            $dayEvents = $this->isolator->isolate($events, $day, $rangeType, $schedule);

            $summaries[] = $this->summaryBuilder->buildDaySummary(
                $dayEvents,
                $rangeType,
                $schedule,
                $this->currentTimeFor($day, $now),
            );

            $day = $day->modify('+1 day');
        }

        return $summaries;
    }

    private function currentTimeFor(
        \DateTimeImmutable $day,
        \DateTimeImmutable $now,
    ): ?\DateTimeImmutable {
        return $day->format('Y-m-d') === $now->format('Y-m-d') ? $now : null;
    }
}
