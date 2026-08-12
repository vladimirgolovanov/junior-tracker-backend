<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

/**
 * Разбор повторяющегося query-параметра вида ?ids=3&ids=5.
 *
 * Через $request->query это не читается: parse_str в PHP оставляет от повторов
 * только последнее значение, если имя не заканчивается на "[]". Поэтому берём
 * сырую строку запроса. Форма ?ids[]=3&ids[]=5 тоже поддерживается.
 */
final class RepeatedQueryParam
{
    /**
     * @return list<int> без нулей и отрицательных; порядок и повторы сохраняются
     */
    public static function ints(?string $queryString, string $name): array
    {
        if (null === $queryString || '' === $queryString) {
            return [];
        }

        $values = [];

        foreach (explode('&', $queryString) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $key = urldecode($key);

            if ($key !== $name && $key !== $name.'[]') {
                continue;
            }

            $value = urldecode($value);

            if (ctype_digit($value) && (int) $value > 0) {
                $values[] = (int) $value;
            }
        }

        return $values;
    }
}
