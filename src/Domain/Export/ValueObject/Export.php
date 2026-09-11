<?php

declare(strict_types=1);

namespace App\Domain\Export\ValueObject;

/**
 * A row of the exports table: one requested export job and its current state.
 */
final readonly class Export
{
    public function __construct(
        public int $id,
        public int $childId,
        public int $requestedBy,
        public ExportType $type,
        public ExportStatus $status,
        public ?\DateTimeImmutable $dateFrom,
        public ?\DateTimeImmutable $dateTo,
        public ?string $s3Key,
        public ?string $error,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $completedAt,
    ) {
    }
}
