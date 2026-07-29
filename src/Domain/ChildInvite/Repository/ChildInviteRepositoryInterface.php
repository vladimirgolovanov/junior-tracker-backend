<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\Repository;

use App\Domain\ChildInvite\Exception\InviteAlreadyAccepted;
use App\Domain\ChildInvite\Exception\InviteNotFound;
use App\Domain\ChildInvite\ValueObject\ChildInvite;
use App\Domain\ChildInvite\ValueObject\NewChildInvite;

interface ChildInviteRepositoryInterface
{
    public function create(NewChildInvite $invite): void;

    /**
     * @throws InviteNotFound если кода нет в базе
     */
    public function findByCode(string $code): ChildInvite;

    /**
     * Помечает приглашение принятым. Одноразовость гарантирована на уровне SQL.
     *
     * @throws InviteAlreadyAccepted если приглашение уже принято (гонка)
     */
    public function markAccepted(int $inviteId, int $userId, \DateTimeImmutable $now): void;
}
