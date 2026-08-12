<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query-параметры GET /api/v2/chart/.
 * Свойства названы как параметры в URL, чтобы ключи ошибок совпадали с ними.
 *
 * additional_data_ids здесь нет: повторяющийся параметр не переживает parse_str,
 * его разбирает RepeatedQueryParam прямо из строки запроса.
 */
final class ChartQuery
{
    public function __construct(
        #[Assert\NotNull(message: 'Query parameter "child_id" is required.')]
        #[Assert\Positive(message: 'Query parameter "child_id" must be a positive integer.')]
        public readonly ?int $child_id = null,

        #[Assert\NotNull(message: 'Query parameter "date_from" is required.')]
        #[Assert\Date(message: 'Query parameter "date_from" must be a valid date (YYYY-MM-DD).')]
        public readonly ?string $date_from = null,

        #[Assert\NotNull(message: 'Query parameter "date_to" is required.')]
        #[Assert\Date(message: 'Query parameter "date_to" must be a valid date (YYYY-MM-DD).')]
        // Даты в формате Y-m-d сравниваются как строки в том же порядке, что и как даты.
        #[Assert\GreaterThanOrEqual(
            propertyPath: 'date_from',
            message: 'Query parameter "date_to" must not be earlier than "date_from".',
        )]
        public readonly ?string $date_to = null,
    ) {
    }
}
