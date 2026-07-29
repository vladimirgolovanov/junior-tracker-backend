<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\ChildInvite\Service\InviteCodeGeneratorInterface;

/**
 * 32 байта энтропии в url-safe base64 без паддинга — код влезает в ссылку,
 * а угадать/перебрать его нельзя. Уникальность страхует индекс ix_child_invites_code.
 */
final readonly class RandomInviteCodeGenerator implements InviteCodeGeneratorInterface
{
    private const BYTES = 32;

    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::BYTES)), '+/', '-_'), '=');
    }
}
