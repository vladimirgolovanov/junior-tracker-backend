<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query-параметры GET /api/v2/events.
 * Свойства названы как параметры в URL, чтобы ключи ошибок совпадали с ними.
 */
final class EventsQuery
{
    public const DEFAULT_LIMIT = 50;
    public const MAX_LIMIT = 200;

    public function __construct(
        #[Assert\NotNull(message: 'Query parameter "child_id" is required.')]
        #[Assert\Positive(message: 'Query parameter "child_id" must be a positive integer.')]
        public readonly ?int $child_id = null,

        #[Assert\Range(
            min: 1,
            max: self::MAX_LIMIT,
            notInRangeMessage: 'Query parameter "limit" must be between {{ min }} and {{ max }}.',
        )]
        public readonly int $limit = self::DEFAULT_LIMIT,

        #[Assert\PositiveOrZero(message: 'Query parameter "offset" must not be negative.')]
        public readonly int $offset = 0,
    ) {
    }
}
