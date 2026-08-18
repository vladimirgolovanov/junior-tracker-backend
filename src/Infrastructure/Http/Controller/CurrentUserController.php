<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\User\CurrentUserMapper;
use App\Application\User\GetCurrentUser;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Кто я и что мне можно. Роль лежит в children[].is_owner: она относится к
 * ребёнку, а не к пользователю, поэтому одним полем не выражается.
 */
final class CurrentUserController
{
    public function __construct(
        private readonly GetCurrentUser $getCurrentUser,
        private readonly CurrentUserMapper $mapper,
    ) {
    }

    #[Route('/api/v2/me', name: 'api_v2_me', methods: ['GET'])]
    #[Authenticated]
    public function __invoke(Request $request): JsonResponse
    {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        return new JsonResponse($this->mapper->toArray(($this->getCurrentUser)($userId)));
    }
}
