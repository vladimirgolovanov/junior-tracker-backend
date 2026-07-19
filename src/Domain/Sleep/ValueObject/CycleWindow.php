<?php

declare(strict_types=1);

namespace App\Domain\Sleep\ValueObject;

final readonly class CycleWindow
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }

    public static function forDates(
        \DateTimeImmutable $firstDay,
        \DateTimeImmutable $lastDay,
    ): self {
        return new self(
            $firstDay->setTime(0, 0),
            $lastDay->modify('+1 day')->setTime(23, 59, 59),
        );
    }
}
