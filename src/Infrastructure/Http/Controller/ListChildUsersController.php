<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\ChildUser\ChildUserMapper;
use App\Application\ChildUser\ListChildUsers;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Кто видит ребёнка. Только для владельца: состав семьи — это список чужих
 * email, отдавать его любому участнику незачем.
 */
final class ListChildUsersController
{
    public function __construct(
        private readonly ListChildUsers $listChildUsers,
        private readonly ChildUserMapper $mapper,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/users',
        name: 'api_v2_child_users',
        requirements: ['childId' => '\d+'],
        methods: ['GET'],
    )]
    #[Authenticated]
    public function __invoke(int $childId, Request $request): JsonResponse
    {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        return new JsonResponse($this->mapper->toArray(($this->listChildUsers)($userId, $childId)));
    }
}
