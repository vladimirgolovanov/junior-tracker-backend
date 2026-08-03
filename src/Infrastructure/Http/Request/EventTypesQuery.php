<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query-параметры GET /api/v2/event_types.
 * Свойство названо как параметр в URL, чтобы имя поля совпадало и в маппинге,
 * и в ключах ошибок валидации.
 */
final class EventTypesQuery
{
    public function __construct(
        #[Assert\NotNull(message: 'Query parameter "child_id" is required.')]
        #[Assert\Positive(message: 'Query parameter "child_id" must be a positive integer.')]
        public readonly ?int $child_id = null,
    ) {
    }
}
