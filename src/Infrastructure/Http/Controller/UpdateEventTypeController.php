<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Event\EventTypeMapper;
use App\Application\Event\UpdateEventType;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\UpdateEventTypeRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateEventTypeController
{
    public function __construct(
        private readonly UpdateEventType $updateEventType,
        private readonly EventTypeMapper $mapper,
    ) {
    }

    #[Route('/api/v2/event_types/{id}', name: 'api_v2_event_types_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[Authenticated]
    public function __invoke(
        int $id,
        Request $request,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        UpdateEventTypeRequest $payload,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $eventType = ($this->updateEventType)($userId, $id, $payload->toPatch());

        return new JsonResponse($this->mapper->one($eventType));
    }
}
