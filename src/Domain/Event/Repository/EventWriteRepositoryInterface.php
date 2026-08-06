<?php

declare(strict_types=1);

namespace App\Domain\Event\Repository;

use App\Domain\Event\ValueObject\EventDraft;
use App\Domain\Event\ValueObject\EventPatch;

interface EventWriteRepositoryInterface
{
    /**
     * @return int id созданного события
     */
    public function create(EventDraft $draft): int;

    public function update(int $id, EventPatch $patch): void;
}
