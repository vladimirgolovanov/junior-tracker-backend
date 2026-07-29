<?php

declare(strict_types=1);

namespace App\Domain\Child\ValueObject;

use App\Domain\Shared\Exception\InvalidValue;

final readonly class Timezone
{
    public function __construct(
        public string $value,
    ) {
        // Не через конструктор \DateTimeZone: тот принимает ещё и смещения
        // ("+03:00") с аббревиатурами, а в childs.timezone нужен IANA-идентификатор.
        if (!in_array($value, \DateTimeZone::listIdentifiers(), true)) {
            throw InvalidValue::unknownTimezone();
        }
    }

    public function toDateTimeZone(): \DateTimeZone
    {
        return new \DateTimeZone($this->value);
    }
}
