<?php

declare(strict_types=1);

namespace App\Application\Export;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Export\Exception\ExportNotFound;
use App\Domain\Export\Repository\ExportRepositoryInterface;
use App\Domain\Export\ValueObject\Export;

/**
 * Owner-only. The status of a single export job of a child.
 */
final readonly class GetExport
{
    public function __construct(
        private ExportRepositoryInterface $exports,
        private ChildAccessRepositoryInterface $childAccess,
    ) {
    }

    /**
     * @throws AccessDenied    the requester does not own the child
     * @throws ExportNotFound  no such export belongs to this child
     */
    public function __invoke(int $userId, int $childId, int $exportId): Export
    {
        if (!$this->childAccess->userIsOwnerOfChild($userId, $childId)) {
            throw AccessDenied::notOwnerOfChild($childId);
        }

        $export = $this->exports->findById($exportId);

        // Guard the child scope too: an export id from another child must not
        // leak through an owner's request for their own child.
        if (null === $export || $export->childId !== $childId) {
            throw ExportNotFound::withId($exportId);
        }

        return $export;
    }
}
