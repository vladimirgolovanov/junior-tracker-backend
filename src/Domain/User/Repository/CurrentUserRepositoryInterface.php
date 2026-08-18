<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Domain\User\ValueObject\CurrentUser;

interface CurrentUserRepositoryInterface
{
    public function find(int $userId): ?CurrentUser;
}
