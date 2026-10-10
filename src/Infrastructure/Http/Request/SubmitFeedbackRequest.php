<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Body of POST /api/v2/feedback. The property is named like the request field
 * so validation-error keys match it.
 */
final class SubmitFeedbackRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Field "text" is required.')]
        #[Assert\Length(max: 5000, maxMessage: 'Field "text" must be at most {{ limit }} characters.')]
        public readonly ?string $text = null,
    ) {
    }
}
