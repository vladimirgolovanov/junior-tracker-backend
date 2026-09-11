<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Application\Export\Storage\ExportStorageInterface;
use AsyncAws\S3\S3Client;

/**
 * Stores export files in an S3-compatible bucket. The bucket name is injected
 * from the environment (see services.yaml); the S3 client is configured by the
 * async-aws bundle.
 */
final readonly class S3ExportStorage implements ExportStorageInterface
{
    public function __construct(
        private S3Client $s3,
        private string $bucket,
    ) {
    }

    public function put(string $key, string $contents): void
    {
        // async-aws is lazy: resolve() forces the request to actually run.
        $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $contents,
            'ContentType' => 'application/json',
        ])->resolve();
    }

    public function read(string $key): iterable
    {
        return $this->s3->getObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ])->getBody()->getChunks();
    }
}
