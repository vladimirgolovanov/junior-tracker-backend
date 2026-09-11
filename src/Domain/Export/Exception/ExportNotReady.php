<?php

declare(strict_types=1);

namespace App\Domain\Export\Exception;

/**
 * Thrown when a download is requested for an export that has not completed yet
 * (still pending/processing, or failed) — there is no file to stream.
 */
final class ExportNotReady extends \RuntimeException
{
    public static function withId(int $exportId): self
    {
        return new self(sprintf('Export %d is not ready for download.', $exportId));
    }
}
