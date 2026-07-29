<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\ChildInvite\CreateChildInvite;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Внутренний контур: владение ребёнком и личность inviter уже проверены
 * доверенным источником выше по стеку, поэтому авторизации здесь нет.
 */
final class CreateChildInviteController
{
    public function __construct(
        private readonly CreateChildInvite $createChildInvite,
    ) {
    }

    #[Route(
        '/internal/children/{childId}/invites',
        name: 'internal_child_invites_create',
        requirements: ['childId' => '\d+'],
        methods: ['POST'],
    )]
    public function __invoke(int $childId, Request $request): JsonResponse
    {
        $invite = $this->createChildInvite->handle(
            $childId,
            $this->inviterId($request),
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

    private function inviterId(Request $request): int
    {
        try {
            $payload = $request->toArray();
        } catch (\Throwable) {
            throw new BadRequestHttpException('Request body must be a JSON object.');
        }

        if (!array_key_exists('inviter_id', $payload)) {
            throw new BadRequestHttpException('Field "inviter_id" is required.');
        }

        if (!is_int($payload['inviter_id'])) {
            throw new BadRequestHttpException('Field "inviter_id" must be an integer.');
        }

        return $payload['inviter_id'];
    }
}
