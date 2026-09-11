<?php

declare(strict_types=1);

namespace App\Application\Export;

use App\Application\Export\Message\GenerateExport;
use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Export\Repository\ExportRepositoryInterface;
use App\Domain\Export\ValueObject\Export;
use App\Domain\Export\ValueObject\ExportType;
use App\Domain\Shared\Exception\InvalidValue;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Owner-only. Records a pending export job and queues its generation; the file
 * is produced asynchronously by the worker. Returns the just-created job so the
 * caller can poll it and, once completed, download it.
 */
final readonly class RequestExport
{
    public function __construct(
        private ExportRepositoryInterface $exports,
        private ChildAccessRepositoryInterface $childAccess,
        private MessageBusInterface $bus,
    ) {
    }

    /**
     * @throws AccessDenied  the requester does not own the child
     * @throws InvalidValue  the date range is reversed
     */
    public function __invoke(
        int $userId,
        int $childId,
        ExportType $type,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ): Export {
        // Exporting everything about a child is an owner action, like listing
        // the family or the invites.
        if (!$this->childAccess->userIsOwnerOfChild($userId, $childId)) {
            throw AccessDenied::notOwnerOfChild($childId);
        }

        if (null !== $from && null !== $to && $from > $to) {
            throw InvalidValue::dateRangeOutOfOrder();
        }

        $id = $this->exports->create($childId, $userId, $type, $from, $to);

        $this->bus->dispatch(new GenerateExport($id));

        $export = $this->exports->findById($id);

        // The row was just inserted in the same request, so it must be there.
        \assert(null !== $export);

        return $export;
    }
}
