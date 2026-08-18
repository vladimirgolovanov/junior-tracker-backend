<?php

declare(strict_types=1);

namespace App\Domain\Account\Repository;

use App\Domain\Account\ValueObject\DeletedAccount;
use App\Domain\Account\ValueObject\StoredCredentials;
use App\Domain\User\ValueObject\Email;

interface AccountDeletionRepositoryInterface
{
    public function findCredentialsByEmail(Email $email): ?StoredCredentials;

    public function findCredentialsById(int $userId): ?StoredCredentials;

    /**
     * Removes the user and everything that would otherwise dangle: children the
     * user owns (or is the last member of) with all their data, invites, API
     * keys and sessions. Atomic.
     */
    public function delete(int $userId): DeletedAccount;
}
