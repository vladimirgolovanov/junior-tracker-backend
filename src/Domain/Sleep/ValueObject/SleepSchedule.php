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

    /**
     * Builds a schedule from stored per-child values (H:i strings), falling back to the defaults
     * whenever a value is missing or invalid. Acts as a read-side safety net: the source column has
     * no DB check constraint and is written by a separate service, so a single bad value must degrade
     * to the default window rather than break the whole sleep summary.
     */
    public static function fromNullableStrings(?string $dayStart, ?string $dayEnd): self
    {
        if (null === $dayStart || null === $dayEnd) {
            return new self();
        }

        if (!self::isValidTime($dayStart) || !self::isValidTime($dayEnd) || $dayStart >= $dayEnd) {
            return new self();
        }

        return new self($dayStart, $dayEnd);
    }

    private static function isValidTime(string $time): bool
    {
        // Reject anything that is not a strict zero-padded H:i (e.g. "24:00", "6:00", "06:00:00").
        $parsed = \DateTimeImmutable::createFromFormat('!H:i', $time);

        return false !== $parsed && $parsed->format('H:i') === $time;
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
