<?php

declare(strict_types=1);

namespace App\Domain\Export\Exception;

final class ExportNotFound extends \RuntimeException
{
    public static function withId(int $exportId): self
    {
        return new self(sprintf('Export %d not found.', $exportId));
    }
}
