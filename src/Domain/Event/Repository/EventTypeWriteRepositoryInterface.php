<?php

declare(strict_types=1);

namespace App\Domain\Event\Repository;

use App\Domain\Event\ValueObject\EventTypeDraft;
use App\Domain\Event\ValueObject\EventTypePatch;

interface EventTypeWriteRepositoryInterface
{
    /**
     * @param EventTypeDraft|null $pairedEnd парный range_end; сохраняется с parent_id
     *                                       созданного типа в одной транзакции с ним
     *
     * @return int id созданного типа (для пары — id старт-типа)
     */
    public function create(EventTypeDraft $draft, ?EventTypeDraft $pairedEnd = null): int;

    public function update(int $id, EventTypePatch $patch): void;
}
