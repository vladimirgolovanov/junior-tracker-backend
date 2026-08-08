<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\ChildInvite\CreateChildInvite;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Публичный контур: inviter — это аутентифицированный пользователь из токена,
 * а его доступ к ребёнку проверяется в CreateChildInvite.
 */
final class CreateChildInviteController
{
    public function __construct(
        private readonly CreateChildInvite $createChildInvite,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/invites',
        name: 'api_v2_child_invites_create',
        requirements: ['childId' => '\d+'],
        methods: ['POST'],
    )]
    #[Authenticated]
    public function __invoke(int $childId, Request $request): JsonResponse
    {
        $inviterId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $invite = $this->createChildInvite->handle(
            $childId,
            $inviterId,
            new \DateTimeImmutable('now'),
        );

        return new JsonResponse(
            [
                'code' => $invite->code,
                'expires_at' => $invite->expiresAt->format(\DateTimeInterface::ATOM),
            ],
            Response::HTTP_CREATED,
        );
    }
}
