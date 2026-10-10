<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Feedback\Message\FeedbackSubmitted;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Serializes FeedbackSubmitted into a plain JSON body for the feedback queue,
 * so a separate (possibly non-PHP) consumer reads a stable contract instead of
 * Symfony's native PHP envelope. This app only publishes feedback and never
 * consumes it, so decode() is unreachable.
 */
final class FeedbackMessageSerializer implements SerializerInterface
{
    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();

        if (!$message instanceof FeedbackSubmitted) {
            throw new \InvalidArgumentException(sprintf(
                'This serializer only supports "%s", got "%s".',
                FeedbackSubmitted::class,
                get_debug_type($message),
            ));
        }

        $body = json_encode([
            'id' => $message->id,
            'user_id' => $message->userId,
            'text' => $message->text,
            'created_at' => $message->createdAt
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format(\DateTimeInterface::ATOM),
        ], JSON_THROW_ON_ERROR);

        return [
            'body' => $body,
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }

    public function decode(array $encodedEnvelope): Envelope
    {
        throw new \LogicException('Feedback messages are consumed by an external service, not this app.');
    }
}
