<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Auth\Service\TokenValueGeneratorInterface;

/**
 * Воспроизводит формат secrets.token_urlsafe() из fastapi-users:
 * 32 случайных байта → base64url без паддинга = 43 url-safe символа.
 */
final readonly class UrlSafeTokenGenerator implements TokenValueGeneratorInterface
{
    private const BYTES = 32;

    public function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(self::BYTES)), '+/', '-_'), '=');
    }
}
