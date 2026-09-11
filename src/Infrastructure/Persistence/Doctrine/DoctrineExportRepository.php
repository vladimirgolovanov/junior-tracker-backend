<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Export\Repository\ExportRepositoryInterface;
use App\Domain\Export\ValueObject\Export;
use App\Domain\Export\ValueObject\ExportStatus;
use App\Domain\Export\ValueObject\ExportType;
use Doctrine\DBAL\Connection;

final readonly class DoctrineExportRepository implements ExportRepositoryInterface
{
    private const UTC = 'UTC';

    private const COLUMNS = 'id, child_id, requested_by, type, status, date_from, date_to, s3_key, error, created_at, completed_at';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function create(
        int $childId,
        int $requestedBy,
        ExportType $type,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
    ): int {
        return (int) $this->connection->fetchOne(
            'INSERT INTO exports (child_id, requested_by, type, status, date_from, date_to)
             VALUES (:childId, :requestedBy, :type, :status, :dateFrom, :dateTo)
             RETURNING id',
            [
                'childId' => $childId,
                'requestedBy' => $requestedBy,
                'type' => $type->value,
                'status' => ExportStatus::Pending->value,
                'dateFrom' => $this->toUtcString($dateFrom),
                'dateTo' => $this->toUtcString($dateTo),
            ],
        );
    }

    public function findById(int $id): ?Export
    {
        $row = $this->connection->fetchAssociative(
            'SELECT '.self::COLUMNS.' FROM exports WHERE id = :id',
            ['id' => $id],
        );

        return false === $row ? null : $this->hydrate($row);
    }

    public function findByChild(int $childId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT '.self::COLUMNS.'
             FROM exports
             WHERE child_id = :childId
             ORDER BY created_at DESC, id DESC',
            ['childId' => $childId],
        );

        return array_map($this->hydrate(...), $rows);
    }

    public function markProcessing(int $id): void
    {
        $this->connection->executeStatement(
            'UPDATE exports SET status = :status WHERE id = :id',
            ['status' => ExportStatus::Processing->value, 'id' => $id],
        );
    }

    public function markCompleted(int $id, string $s3Key, \DateTimeImmutable $completedAt): void
    {
        $this->connection->executeStatement(
            'UPDATE exports
             SET status = :status, s3_key = :s3Key, completed_at = :completedAt
             WHERE id = :id',
            [
                'status' => ExportStatus::Completed->value,
                's3Key' => $s3Key,
                'completedAt' => $this->toUtcString($completedAt),
                'id' => $id,
            ],
        );
    }

    public function markFailed(int $id, string $error): void
    {
        $this->connection->executeStatement(
            'UPDATE exports SET status = :status, error = :error WHERE id = :id',
            ['status' => ExportStatus::Failed->value, 'error' => $error, 'id' => $id],
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Export
    {
        return new Export(
            id: (int) $row['id'],
            childId: (int) $row['child_id'],
            requestedBy: (int) $row['requested_by'],
            type: ExportType::from($row['type']),
            status: ExportStatus::from($row['status']),
            dateFrom: $this->toDateTime($row['date_from']),
            dateTo: $this->toDateTime($row['date_to']),
            s3Key: $row['s3_key'],
            error: $row['error'],
            createdAt: new \DateTimeImmutable($row['created_at']),
            completedAt: $this->toDateTime($row['completed_at']),
        );
    }

    private function toDateTime(?string $value): ?\DateTimeImmutable
    {
        return null === $value ? null : new \DateTimeImmutable($value);
    }

    private function toUtcString(?\DateTimeImmutable $moment): ?string
    {
        return $moment?->setTimezone(new \DateTimeZone(self::UTC))->format('Y-m-d H:i:sP');
    }
}
