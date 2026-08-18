<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Account\DeleteAccount;
use App\Domain\Shared\Exception\InvalidValue;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * In-app deletion path required by the Google Play account deletion policy.
 * The password is re-checked even though the caller is already authenticated:
 * deleting an owner takes the child's entire history with it, and a stolen
 * bearer token must not be enough to do that.
 */
final class DeleteAccountController
{
    public function __construct(
        private readonly DeleteAccount $deleteAccount,
    ) {
    }

    #[Route('/api/v2/account', name: 'api_v2_account_delete', methods: ['DELETE'])]
    #[Authenticated]
    public function __invoke(Request $request): Response
    {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $this->deleteAccount->byUserId($userId, $this->password($request));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function password(Request $request): string
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            throw new BadRequestHttpException('Request body must be a JSON object.');
        }

        if (!array_key_exists('password', $payload)) {
            throw InvalidValue::missingField('password');
        }

        if (!is_string($payload['password'])) {
            throw InvalidValue::notAString('password');
        }

        return $payload['password'];
    }
}
