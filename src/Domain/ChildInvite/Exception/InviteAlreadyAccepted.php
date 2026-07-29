<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\Exception;

final class InviteAlreadyAccepted extends \DomainException
{
    public static function create(): self
    {
        return new self('Invite has already been accepted.');
    }
}
