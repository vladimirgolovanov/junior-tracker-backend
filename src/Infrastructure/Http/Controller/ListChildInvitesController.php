<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\ChildInvite\ChildInviteMapper;
use App\Application\ChildInvite\ListChildInvites;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ListChildInvitesController
{
    public function __construct(
        private readonly ListChildInvites $listChildInvites,
        private readonly ChildInviteMapper $mapper,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/invites',
        name: 'api_v2_child_invites',
        requirements: ['childId' => '\d+'],
        methods: ['GET'],
    )]
    #[Authenticated]
    public function __invoke(int $childId, Request $request): JsonResponse
    {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $invites = ($this->listChildInvites)($userId, $childId);

        return new JsonResponse($this->mapper->toArray($invites, new \DateTimeImmutable('now')));
    }
}
