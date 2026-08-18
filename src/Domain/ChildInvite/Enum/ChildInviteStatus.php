<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\Enum;

enum ChildInviteStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Expired = 'expired';
}
