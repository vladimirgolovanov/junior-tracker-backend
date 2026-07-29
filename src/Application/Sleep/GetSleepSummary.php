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
        $firstDay = new \DateTimeImmutable($firstDay->format('Y-m-d'), $timezone);
        $lastDay = new \DateTimeImmutable($lastDay->format('Y-m-d'), $timezone);

        $schedule = new SleepSchedule();
        $rangeType = $this->eventTypeRepository->findRangeType($childId, 'sleep_start');
        $window = CycleWindow::forDates($firstDay->modify('-1 day'), $lastDay);

        $events = $this->eventRepository->findByChildAndTypes(
            $childId,
            [$rangeType->startId, $rangeType->endId],
            $window->from,
            $window->to,
            $timezone,
        );

        if ([] === $this->isolator->isolate($events, $lastDay, $rangeType, $schedule)) {
            $firstDay = $firstDay->modify('-1 day');
            $lastDay = $lastDay->modify('-1 day');
        }

        $summaries = [];
        $day = $firstDay;

        while ($day <= $lastDay) {
            $dayEvents = $this->isolator->isolate($events, $day, $rangeType, $schedule);

            $summaries[] = $this->summaryBuilder->buildDaySummary(
                $dayEvents,
                $rangeType,
                $schedule,
                $this->currentTimeFor($day, $lastDay, $now),
            );

            $day = $day->modify('+1 day');
        }

        return $summaries;
    }

    private function currentTimeFor(
        \DateTimeImmutable $day,
        \DateTimeImmutable $lastDay,
        \DateTimeImmutable $now,
    ): ?\DateTimeImmutable {
        if ($day->format('Y-m-d') !== $lastDay->format('Y-m-d')) {
            return null;
        }

        return $now < $day->modify('+2 day') ? $now : null;
    }
}
