<?php

declare(strict_types=1);

namespace App\Domain\Export\Generator;

use App\Domain\Export\ValueObject\ExportType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Builds the payload for one export type. Each implementation handles a single
 * ExportType; the worker picks one by type through the registry. Adding a new
 * export type means adding an implementation — the queue/storage/download
 * pipeline stays untouched.
 */
#[AutoconfigureTag('app.export_generator')]
interface ExportGenerator
{
    public function type(): ExportType;

    /**
     * @return array<string, mixed> the structure serialized to JSON and stored
     */
    public function generate(int $childId, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to): array;
}
