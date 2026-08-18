<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\ChildUser\ChildUserMapper;
use App\Application\ChildUser\UpdateChildUserOwnership;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\UpdateChildUserRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateChildUserController
{
    public function __construct(
        private readonly UpdateChildUserOwnership $updateOwnership,
        private readonly ChildUserMapper $mapper,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/users/{userId}',
        name: 'api_v2_child_users_update',
        requirements: ['childId' => '\d+', 'userId' => '\d+'],
        methods: ['PATCH'],
    )]
    #[Authenticated]
    public function __invoke(
        int $childId,
        int $userId,
        Request $request,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        UpdateChildUserRequest $payload,
    ): JsonResponse {
        $actorId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $member = ($this->updateOwnership)($actorId, $childId, $userId, (bool) $payload->is_owner);

        return new JsonResponse($this->mapper->one($member));
    }
}
