<?php

declare(strict_types=1);

namespace App\Application\Export;

/**
 * A ready-to-stream export file: its contents in chunks plus the filename to
 * offer the client.
 */
final readonly class ExportDownload
{
    /**
     * @param iterable<string> $chunks
     */
    public function __construct(
        public iterable $chunks,
        public string $filename,
    ) {
    }
}
