<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Account\DeleteAccount;
use App\Domain\Account\Exception\InvalidCredentials;
use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\User\ValueObject\Email;
use App\Infrastructure\Http\Page\AccountDeletionPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Deletion from the public page. Failures are rendered back into the form on
 * purpose: ProblemJsonExceptionListener only covers /api/v2/, so an uncaught
 * exception here would show a bare Symfony error page to the user — and to the
 * Play reviewer checking that the pathway works.
 */
final class SubmitAccountDeletionController
{
    private const CONFIRMATION_REQUIRED = 'Please confirm that you understand the deletion is permanent.';
    private const INVALID_CREDENTIALS = 'We could not verify these credentials. Check the email and password and try again.';

    public function __construct(
        private readonly DeleteAccount $deleteAccount,
        private readonly AccountDeletionPage $page,
    ) {
    }

    #[Route('/account-deletion', name: 'account_deletion_submit', methods: ['POST'])]
    #[RateLimit(limiter: 'account_deletion')]
    public function __invoke(Request $request): Response
    {
        if ('1' !== $request->request->get('confirm')) {
            return $this->rejected(self::CONFIRMATION_REQUIRED);
        }

        try {
            $email = new Email((string) $request->request->get('email', ''));
        } catch (InvalidValue) {
            // A malformed address cannot belong to anyone, so it gets the same
            // answer as a wrong password: the form must not confirm who exists.
            return $this->rejected(self::INVALID_CREDENTIALS);
        }

        try {
            $this->deleteAccount->byEmail($email, (string) $request->request->get('password', ''));
        } catch (InvalidCredentials) {
            return $this->rejected(self::INVALID_CREDENTIALS);
        }

        return $this->html($this->page->deleted(), Response::HTTP_OK);
    }

    private function rejected(string $error): Response
    {
        return $this->html($this->page->form($error), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function html(string $body, int $status): Response
    {
        return new Response($body, $status, ['Content-Type' => 'text/html; charset=utf-8']);
    }
}
