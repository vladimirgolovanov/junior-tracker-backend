<?php

declare(strict_types=1);

namespace App\Domain\Sleep\Enum;

enum SleepState: string
{
    case Asleep = 'sleep';
    case Awake = 'awake';
}
