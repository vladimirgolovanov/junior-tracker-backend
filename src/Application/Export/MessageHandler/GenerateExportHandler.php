<?php

declare(strict_types=1);

namespace App\Application\Export\MessageHandler;

use App\Application\Export\Generator\ExportGeneratorRegistry;
use App\Application\Export\Message\GenerateExport;
use App\Application\Export\Storage\ExportStorageInterface;
use App\Domain\Export\Repository\ExportRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Worker side of an export: build the payload for the job, store it, and record
 * the outcome on the job row. Terminal jobs are skipped so a redelivered
 * message cannot overwrite a finished export; a crash mid-flight (status still
 * processing) is safe to re-run.
 */
#[AsMessageHandler]
final readonly class GenerateExportHandler
{
    public function __construct(
        private ExportRepositoryInterface $exports,
        private ExportGeneratorRegistry $generators,
        private ExportStorageInterface $storage,
    ) {
    }

    public function __invoke(GenerateExport $message): void
    {
        $export = $this->exports->findById($message->exportId);

        if (null === $export || $export->status->isTerminal()) {
            return;
        }

        $this->exports->markProcessing($export->id);

        try {
            $payload = $this->generators->get($export->type)->generate(
                $export->childId,
                $export->dateFrom,
                $export->dateTo,
            );

            $json = json_encode(
                $payload,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
            );

            $key = sprintf('exports/%d/%d.json', $export->childId, $export->id);
            $this->storage->put($key, $json);

            $this->exports->markCompleted(
                $export->id,
                $key,
                new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
            );
        } catch (\Throwable $exception) {
            // Record the failure as the terminal state the owner sees; they can
            // request a fresh export. The message is acknowledged rather than
            // retried, so a generation bug does not loop forever.
            $this->exports->markFailed($export->id, $exception->getMessage());
        }
    }
}
