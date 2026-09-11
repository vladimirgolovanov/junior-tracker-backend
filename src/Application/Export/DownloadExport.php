<?php

declare(strict_types=1);

namespace App\Application\Export;

use App\Application\Export\Storage\ExportStorageInterface;
use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Export\Exception\ExportNotFound;
use App\Domain\Export\Exception\ExportNotReady;
use App\Domain\Export\Repository\ExportRepositoryInterface;
use App\Domain\Export\ValueObject\ExportStatus;

/**
 * Owner-only. Resolves a completed export to a streamable file. The backend
 * proxies the bytes from storage, so the object store is never exposed.
 */
final readonly class DownloadExport
{
    public function __construct(
        private ExportRepositoryInterface $exports,
        private ChildAccessRepositoryInterface $childAccess,
        private ExportStorageInterface $storage,
    ) {
    }

    /**
     * @throws AccessDenied    the requester does not own the child
     * @throws ExportNotFound  no such export belongs to this child
     * @throws ExportNotReady  the export has not completed yet
     */
    public function __invoke(int $userId, int $childId, int $exportId): ExportDownload
    {
        if (!$this->childAccess->userIsOwnerOfChild($userId, $childId)) {
            throw AccessDenied::notOwnerOfChild($childId);
        }

        $export = $this->exports->findById($exportId);

        if (null === $export || $export->childId !== $childId) {
            throw ExportNotFound::withId($exportId);
        }

        if (ExportStatus::Completed !== $export->status || null === $export->s3Key) {
            throw ExportNotReady::withId($exportId);
        }

        $filename = sprintf('export-%s-%d.json', $export->type->value, $export->id);

        return new ExportDownload($this->storage->read($export->s3Key), $filename);
    }
}
