<?php

declare(strict_types=1);

namespace App\Application\Feedback;

use App\Domain\Feedback\ValueObject\Feedback;

/**
 * Serializes a feedback row for the API.
 */
final class FeedbackMapper
{
    /**
     * @return array<string, mixed>
     */
    public function one(Feedback $feedback): array
    {
        return [
            'id' => $feedback->id,
            'user_id' => $feedback->userId,
            'text' => $feedback->text,
            'created_at' => $feedback->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
