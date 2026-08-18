<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\ChildUser\RemoveChildUser;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RemoveChildUserController
{
    public function __construct(
        private readonly RemoveChildUser $removeChildUser,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/users/{userId}',
        name: 'api_v2_child_users_remove',
        requirements: ['childId' => '\d+', 'userId' => '\d+'],
        methods: ['DELETE'],
    )]
    #[Authenticated]
    public function __invoke(int $childId, int $userId, Request $request): Response
    {
        $actorId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        ($this->removeChildUser)($actorId, $childId, $userId);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
