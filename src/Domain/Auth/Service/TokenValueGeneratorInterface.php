<?php

declare(strict_types=1);

namespace App\Domain\Auth\Service;

interface TokenValueGeneratorInterface
{
    /**
     * Криптостойкая url-safe строка длиной не больше AccessToken::MAX_LENGTH.
     */
    public function generate(): string;
}
