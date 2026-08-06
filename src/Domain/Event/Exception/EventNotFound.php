<?php

declare(strict_types=1);

namespace App\Domain\Event\Exception;

final class EventNotFound extends \RuntimeException
{
    public static function byId(int $id): self
    {
        return new self(sprintf('Event %d not found.', $id));
    }
}
