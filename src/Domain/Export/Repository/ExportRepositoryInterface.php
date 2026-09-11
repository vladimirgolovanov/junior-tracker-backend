<?php

declare(strict_types=1);

namespace App\Domain\Export\Repository;

use App\Domain\Export\ValueObject\Export;
use App\Domain\Export\ValueObject\ExportType;

interface ExportRepositoryInterface
{
    /**
     * Record a new pending job and return its id.
     */
    public function create(
        int $childId,
        int $requestedBy,
        ExportType $type,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
    ): int;

    public function findById(int $id): ?Export;

    /**
     * Export history of a child, newest first.
     *
     * @return Export[]
     */
    public function findByChild(int $childId): array;

    public function markProcessing(int $id): void;

    public function markCompleted(int $id, string $s3Key, \DateTimeImmutable $completedAt): void;

    public function markFailed(int $id, string $error): void;
}
