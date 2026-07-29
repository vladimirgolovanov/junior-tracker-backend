<?php

declare(strict_types=1);

namespace App\Domain\User\Service;

use App\Domain\User\ValueObject\PlainPassword;

interface PasswordHasherInterface
{
    public function hash(PlainPassword $password): string;
}
