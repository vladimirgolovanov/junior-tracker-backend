<?php

declare(strict_types=1);

namespace App\Application\Export\Generator;

use App\Domain\Child\Repository\ChildRepositoryInterface;
use App\Domain\Event\Repository\EventReadRepositoryInterface;
use App\Domain\Event\ValueObject\EventDetails;
use App\Domain\Export\Generator\ExportGenerator;
use App\Domain\Export\ValueObject\ExportType;

/**
 * Exports a child's events as a flat list. Each event carries its type name for
 * human readability, so no separate event_types section is needed. Times are
 * wall-clock in the child's timezone (no offset); the timezone itself is stated
 * once in the child block.
 */
final readonly class EventsExportGenerator implements ExportGenerator
{
    // Naive local time, e.g. "2026-09-01T10:00:00" — no offset suffix.
    private const NAIVE_DATETIME = 'Y-m-d\TH:i:s';

    public function __construct(
        private EventReadRepositoryInterface $events,
        private ChildRepositoryInterface $children,
    ) {
    }

    public function type(): ExportType
    {
        return ExportType::Events;
    }

    public function generate(int $childId, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array
    {
        $timezone = $this->children->findTimezone($childId);
        $events = $this->events->findAllByChild($childId, $from, $to, $timezone);

        return [
            'child' => [
                'name' => $this->children->findName($childId),
                'timezone' => $timezone->getName(),
            ],
            'events' => array_map($this->one(...), $events),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function one(EventDetails $event): array
    {
        // volume/description are included only when present.
        $row = [
            'name' => $event->name,
            'occurred_at' => $event->occurredAt->format(self::NAIVE_DATETIME),
        ];

        if (null !== $event->volume) {
            $row['volume'] = $event->volume;
        }

        if (null !== $event->description) {
            $row['description'] = $event->description;
        }

        return $row;
    }
}
