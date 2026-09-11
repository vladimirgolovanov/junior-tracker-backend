<?php

declare(strict_types=1);

namespace App\Application\Export\Storage;

/**
 * Where generated export files live. Kept behind an interface so the pipeline
 * does not depend on a concrete object store.
 */
interface ExportStorageInterface
{
    public function put(string $key, string $contents): void;

    /**
     * @return iterable<string> the stored object in chunks, for streaming to the client
     */
    public function read(string $key): iterable;
}
