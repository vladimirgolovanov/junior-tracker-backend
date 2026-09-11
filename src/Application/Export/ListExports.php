<?php

declare(strict_types=1);

namespace App\Application\Export;

use App\Domain\Auth\Exception\AccessDenied;
use App\Domain\Child\Repository\ChildAccessRepositoryInterface;
use App\Domain\Export\Repository\ExportRepositoryInterface;
use App\Domain\Export\ValueObject\Export;

/**
 * Owner-only. The export history of a child, newest first.
 */
final readonly class ListExports
{
    public function __construct(
        private ExportRepositoryInterface $exports,
        private ChildAccessRepositoryInterface $childAccess,
    ) {
    }

    /**
     * @return Export[]
     *
     * @throws AccessDenied the requester does not own the child
     */
    public function __invoke(int $userId, int $childId): array
    {
        if (!$this->childAccess->userIsOwnerOfChild($userId, $childId)) {
            throw AccessDenied::notOwnerOfChild($childId);
        }

        return $this->exports->findByChild($childId);
    }
}
