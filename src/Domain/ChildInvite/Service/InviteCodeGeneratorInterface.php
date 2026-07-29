<?php

declare(strict_types=1);

namespace App\Domain\ChildInvite\Service;

interface InviteCodeGeneratorInterface
{
    /**
     * Непрогнозируемый код, который уходит в ссылку-приглашение.
     */
    public function generate(): string;
}
