<?php

declare(strict_types=1);

namespace App\Application\Export;

use App\Domain\Export\ValueObject\Export;

/**
 * Serializes an export job for the API. The storage key is deliberately not
 * exposed — the file is reached only through the download endpoint.
 */
final class ExportMapper
{
    /**
     * @param Export[] $exports
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(array $exports): array
    {
        return array_map($this->one(...), $exports);
    }

    /**
     * @return array<string, mixed>
     */
    public function one(Export $export): array
    {
        return [
            'id' => $export->id,
            'type' => $export->type->value,
            'status' => $export->status->value,
            'date_from' => $export->dateFrom?->format(\DateTimeInterface::ATOM),
            'date_to' => $export->dateTo?->format(\DateTimeInterface::ATOM),
            'error' => $export->error,
            'created_at' => $export->createdAt->format(\DateTimeInterface::ATOM),
            'completed_at' => $export->completedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
