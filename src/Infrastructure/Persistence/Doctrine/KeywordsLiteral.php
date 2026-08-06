<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

/**
 * Postgres-массив text[] в INSERT/UPDATE.
 * DBAL не умеет биндить массив как значение колонки, поэтому строим
 * ARRAY[:k0, :k1]::text[] из плейсхолдеров, а сами слова уходят обычными параметрами.
 */
final readonly class KeywordsLiteral
{
    public const NULL_LITERAL = 'NULL::text[]';

    /**
     * @param string[]|null $keywords
     *
     * @return array{string, array<string, string>} SQL-фрагмент и параметры к нему
     */
    public static function build(?array $keywords, string $prefix = 'keyword'): array
    {
        if (null === $keywords) {
            return [self::NULL_LITERAL, []];
        }

        $placeholders = [];
        $params = [];

        foreach (array_values($keywords) as $index => $keyword) {
            $placeholders[] = ':'.$prefix.$index;
            $params[$prefix.$index] = $keyword;
        }

        return [sprintf('ARRAY[%s]::text[]', implode(', ', $placeholders)), $params];
    }
}
