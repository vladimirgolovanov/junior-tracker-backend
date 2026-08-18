<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Тело PATCH /api/v2/children/{childId}/users/{userId}.
 * В отличие от остальных PATCH-DTO поле обязательное: менять здесь больше
 * нечего, и запрос без него не имеет смысла.
 */
final class UpdateChildUserRequest
{
    public function __construct(
        #[Assert\NotNull(message: 'Field "is_owner" is required.')]
        public readonly ?bool $is_owner = null,
    ) {
    }
}
