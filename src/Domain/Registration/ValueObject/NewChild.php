<?php

declare(strict_types=1);

namespace App\Domain\Registration\ValueObject;

use App\Domain\Child\ValueObject\Timezone;

final readonly class NewChild
{
    public function __construct(
        public string $name,
        public Timezone $timezone,
    ) {
    }
}
