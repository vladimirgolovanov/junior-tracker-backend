<?php

declare(strict_types=1);

namespace App\Domain\ChildUser\Exception;

/**
 * A child without an owner can never have its members managed again — nobody
 * would pass the ownership check — and the only way out would be editing the
 * database by hand. So the last owner can be neither demoted nor removed.
 */
final class LastOwnerProtected extends \RuntimeException
{
    public static function create(int $childId): self
    {
        return new self(sprintf('Child %d would be left without an owner.', $childId));
    }
}
