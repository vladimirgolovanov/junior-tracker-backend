<?php

declare(strict_types=1);

namespace App\Domain\Export\ValueObject;

/**
 * Lifecycle of an export job. Pending/Processing are transient; Completed/Failed
 * are terminal — a completed job has a file in storage, a failed one an error.
 */
enum ExportStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return self::Completed === $this || self::Failed === $this;
    }
}
