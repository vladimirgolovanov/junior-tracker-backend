<?php

declare(strict_types=1);

namespace App\Domain\Feedback\Repository;

use App\Domain\Feedback\ValueObject\Feedback;

interface FeedbackRepositoryInterface
{
    /**
     * Store a new piece of feedback and return the created row.
     */
    public function create(int $userId, string $text): Feedback;
}
