<?php

declare(strict_types=1);

namespace App\Application\Export\Message;

/**
 * Queued command: build the file for the export job with this id. Carries only
 * the id — the worker reads the job's parameters from the database so the
 * message stays small and always reflects the stored state.
 */
final readonly class GenerateExport
{
    public function __construct(
        public int $exportId,
    ) {
    }
}
