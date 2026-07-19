<?php

declare(strict_types=1);

namespace App\Domain\Sleep\Enum;

enum DayPart: string
{
    case Day = 'day';
    case Night = 'night';
}
