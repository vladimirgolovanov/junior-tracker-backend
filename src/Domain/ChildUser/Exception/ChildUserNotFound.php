<?php

declare(strict_types=1);

namespace App\Domain\ChildUser\Exception;

final class ChildUserNotFound extends \RuntimeException
{
    public static function create(int $childId, int $userId): self
    {
        return new self(sprintf('User %d is not a member of child %d.', $userId, $childId));
    }
}
