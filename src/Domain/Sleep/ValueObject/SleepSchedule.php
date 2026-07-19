<?php

declare(strict_types=1);

namespace App\Domain\Sleep\ValueObject;

final readonly class SleepSchedule
{
    public function __construct(
        private string $dayStart = '06:00',
        private string $dayEnd = '20:00',
    ) {
    }

    public function dayStartAt(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $this->boundary($date, $this->dayStart);
    }

    public function dayEndAt(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $this->boundary($date, $this->dayEnd);
    }

    private function boundary(\DateTimeImmutable $date, string $time): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat(
            'Y-m-d H:i',
            $date->format('Y-m-d').' '.$time,
            $date->getTimezone(),
        );
    }
}
