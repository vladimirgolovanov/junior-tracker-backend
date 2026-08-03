<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class AccessDenied extends \RuntimeException
{
    public static function toChild(int $childId): self
    {
        return new self(sprintf('User is not allowed to access child %d.', $childId));
    }
}
