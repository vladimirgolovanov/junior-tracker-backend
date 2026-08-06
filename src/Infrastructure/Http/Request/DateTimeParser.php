<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use App\Domain\Shared\Exception\InvalidValue;

/**
 * Разбор момента времени из тела запроса.
 * Не через констрейнты валидатора: Assert\DateTime умеет только 'Y-m-d H:i:s',
 * а клиент присылает ISO 8601 ("2026-08-03T11:40:00Z", "...+03:00").
 */
final class DateTimeParser
{
    public static function parse(string $value, string $field): \DateTimeImmutable
    {
        try {
            $moment = new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw InvalidValue::malformedDateTime($field);
        }

        // Строка без смещения ("2026-08-03 11:40") молча берёт таймзону сервера,
        // а это не то, что имел в виду клиент.
        if (!preg_match('/(Z|[+-]\d{2}:?\d{2})$/', trim($value))) {
            throw InvalidValue::malformedDateTime($field);
        }

        return $moment->setTimezone(new \DateTimeZone('UTC'));
    }
}
