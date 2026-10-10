<?php

declare(strict_types=1);

namespace App\Application\Feedback;

use App\Application\Feedback\Message\FeedbackSubmitted;
use App\Domain\Feedback\Repository\FeedbackRepositoryInterface;
use App\Domain\Feedback\ValueObject\Feedback;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Stores a user's feedback and publishes it to the feedback queue for a separate
 * consumer. The database row is the source of truth: a failed publish is logged
 * (and so reported to Sentry) but never fails the request, so feedback is never
 * lost even when the broker is down.
 */
final readonly class SubmitFeedback
{
    public function __construct(
        private FeedbackRepositoryInterface $feedback,
        private MessageBusInterface $bus,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(int $userId, string $text): Feedback
    {
        $feedback = $this->feedback->create($userId, $text);

        try {
            $this->bus->dispatch(new FeedbackSubmitted(
                $feedback->id,
                $feedback->userId,
                $feedback->text,
                $feedback->createdAt,
            ));
        } catch (\Throwable $e) {
            // Best-effort publish: the feedback is already stored, so swallow the
            // failure and report it instead of failing the user's request.
            $this->logger->error('Failed to publish feedback to queue', [
                'exception' => $e,
                'feedback_id' => $feedback->id,
            ]);
        }

        return $feedback;
    }
}
