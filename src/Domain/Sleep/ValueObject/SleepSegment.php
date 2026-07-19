<?php

declare(strict_types=1);

namespace App\Domain\Sleep\ValueObject;

use App\Domain\Sleep\Enum\DayPart;
use App\Domain\Sleep\Enum\SleepState;

final readonly class SleepSegment
{
    public function __construct(
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        public SleepState $state,
        public DayPart $dayPart,
        public ?int $napNumber = null,
        public bool $isCurrent = false,
    ) {
    }

    public function durationInSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }
}
