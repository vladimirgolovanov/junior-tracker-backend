<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Child\Repository\ChildRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class DoctrineChildRepository implements ChildRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function findTimezone(int $childId): \DateTimeZone
    {
        $timezone = $this->connection->fetchOne(
            'SELECT timezone FROM childs WHERE id = :id',
            ['id' => $childId],
        );

        return new \DateTimeZone($timezone);
    }
}
