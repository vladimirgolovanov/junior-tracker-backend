<?php

declare(strict_types=1);

namespace App\Domain\Status\ValueObject;

final readonly class CurrentSleepState
{
    private function __construct(
        public bool $isCurrentlyAsleep,
        public int $sleepMinutes,
        public int $awakeMinutes,
    ) {
    }

    public static function unknown(): self
    {
        return new self(false, 0, 0);
    }

    public static function asleep(int $minutes): self
    {
        return new self(true, $minutes, 0);
    }

    public static function awake(int $minutes): self
    {
        return new self(false, 0, $minutes);
    }
}
