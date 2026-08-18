<?php

declare(strict_types=1);

namespace App\Domain\Account\ValueObject;

/**
 * What the deletion actually removed. Children are listed separately because
 * deleting an owner wipes the child and every co-parent's access to it.
 */
final readonly class DeletedAccount
{
    /**
     * @param int[] $deletedChildIds
     */
    public function __construct(
        public int $userId,
        public array $deletedChildIds,
    ) {
    }
}
