<?php

declare(strict_types=1);

namespace App\Application\Account;

use App\Domain\Account\Exception\InvalidCredentials;
use App\Domain\Account\Repository\AccountDeletionRepositoryInterface;
use App\Domain\Account\ValueObject\DeletedAccount;
use App\Domain\Account\ValueObject\StoredCredentials;
use App\Domain\User\Service\PasswordHasherInterface;
use App\Domain\User\ValueObject\Email;

/**
 * Account deletion required by the Google Play "app account deletion" policy.
 * Two entry points, one rule: the password is always re-checked, because the
 * operation is irreversible and takes the whole child's history with it.
 */
final readonly class DeleteAccount
{
    /**
     * Real argon2id hash of a value nobody uses. Verifying against it keeps the
     * response time of an unknown email close to that of a known one, so the
     * public form cannot be used to enumerate registered addresses.
     */
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=3,p=4$dkZieGYuU2M0cEdqcGlZaA$Oma8uNXM07yMj4h6Hh9pzHa2WPRQRgGrrFQIHihSdpU';

    public function __construct(
        private AccountDeletionRepositoryInterface $repository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * In-app path: the user is already authenticated, the password only
     * confirms the intent and blocks a stolen bearer token.
     *
     * @throws InvalidCredentials
     */
    public function byUserId(int $userId, string $password): DeletedAccount
    {
        return $this->verifyAndDelete($this->repository->findCredentialsById($userId), $password);
    }

    /**
     * Public web path: the password is the only proof of ownership.
     *
     * @throws InvalidCredentials
     */
    public function byEmail(Email $email, string $password): DeletedAccount
    {
        return $this->verifyAndDelete($this->repository->findCredentialsByEmail($email), $password);
    }

    /**
     * @throws InvalidCredentials
     */
    private function verifyAndDelete(?StoredCredentials $credentials, string $password): DeletedAccount
    {
        $matches = $this->passwordHasher->verify(
            $password,
            null !== $credentials ? $credentials->hashedPassword : self::DUMMY_HASH,
        );

        if (null === $credentials || !$matches) {
            throw InvalidCredentials::create();
        }

        return $this->repository->delete($credentials->userId);
    }
}
