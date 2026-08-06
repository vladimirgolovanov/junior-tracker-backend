<?php

declare(strict_types=1);

namespace App\Domain\Event\Service;

/**
 * Имя парного range_end-типа. Правило повторяет дефолтный набор
 * (sleep_start/sleep_end, breastfeeding_start/breastfeeding_end).
 */
final readonly class RangeEndNameResolver
{
    private const START_SUFFIX = '_start';
    private const END_SUFFIX = '_end';

    public function resolve(string $startName): string
    {
        if (str_ends_with($startName, self::START_SUFFIX)) {
            return substr($startName, 0, -strlen(self::START_SUFFIX)).self::END_SUFFIX;
        }

        return $startName.self::END_SUFFIX;
    }
}
