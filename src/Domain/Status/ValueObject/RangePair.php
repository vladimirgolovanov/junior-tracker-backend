<?php

declare(strict_types=1);

namespace App\Domain\Status\ValueObject;

use App\Domain\Event\ValueObject\EventType;
use App\Domain\Event\ValueObject\EventDetails;

/**
 * Пара range/range_end и её текущее состояние.
 * Пара «открыта», если последнее событие любой из половин — начало.
 * «Спит / не спит» — частный случай этого правила для пары sleep_start.
 */
final readonly class RangePair
{
    public function __construct(
        public EventType $start,
        public EventType $end,
        public ?EventDetails $lastEvent,
    ) {
    }

    public function isOpen(): bool
    {
        return null !== $this->lastEvent && $this->lastEvent->eventTypeId === $this->start->id;
    }

    /**
     * Половина, которую логично записать следующей: открыта — значит пора закрывать.
     */
    public function nextEventType(): EventType
    {
        return $this->isOpen() ? $this->end : $this->start;
    }
}
