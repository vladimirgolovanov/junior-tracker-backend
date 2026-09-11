<?php

declare(strict_types=1);

namespace App\Domain\Export\ValueObject;

/**
 * A kind of export. Events is the first and only implemented type; other types
 * (e.g. sleep analytics) plug in by adding a case here and a matching generator.
 */
enum ExportType: string
{
    case Events = 'events';

    /**
     * Accepted values, for request validation (Assert\Choice).
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
