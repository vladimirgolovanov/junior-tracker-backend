<?php

declare(strict_types=1);

namespace App\Domain\Chart\ValueObject;

final readonly class Chart
{
    /**
     * @param list<SleepInterval>          $sleepData      chronological
     * @param array<int, list<ChartEvent>> $additionalData event_type_id => markers, one key per requested type
     */
    public function __construct(
        public array $sleepData,
        public array $additionalData,
    ) {
    }
}
