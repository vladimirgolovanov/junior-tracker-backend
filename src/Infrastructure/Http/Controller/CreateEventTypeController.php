<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Event\CreateEventType;
use App\Application\Event\EventTypeMapper;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\CreateEventTypeRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateEventTypeController
{
    public function __construct(
        private readonly CreateEventType $createEventType,
        private readonly EventTypeMapper $mapper,
    ) {
    }

    #[Route('/api/v2/event_types/', name: 'api_v2_event_types_create', methods: ['POST'])]
    #[Authenticated]
    public function __invoke(
        Request $request,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CreateEventTypeRequest $payload,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $eventType = ($this->createEventType)($userId, $payload->toDraft());

        return new JsonResponse($this->mapper->one($eventType), Response::HTTP_CREATED);
    }
}
