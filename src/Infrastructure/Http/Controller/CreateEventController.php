<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Event\CreateEvent;
use App\Application\Event\EventMapper;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\CreateEventRequest;
use App\Infrastructure\Http\Request\CreateRangeEventRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateEventController
{
    public function __construct(
        private readonly CreateEvent $createEvent,
        private readonly EventMapper $mapper,
    ) {
    }

    #[Route('/api/v2/events', name: 'api_v2_events_create', methods: ['POST'])]
    #[Authenticated]
    public function create(
        Request $request,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CreateEventRequest $payload,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $event = $this->createEvent->create($userId, $payload->toDraft());

        return new JsonResponse($this->mapper->one($event), Response::HTTP_CREATED);
    }

    #[Route('/api/v2/events/range', name: 'api_v2_events_create', methods: ['POST'])]
    #[Authenticated]
    public function range(
        Request $request,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CreateRangeEventRequest $payload,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $event = $this->createEvent->createRange($userId, $payload->toDraft());

        return new JsonResponse($this->mapper->one($event), Response::HTTP_CREATED);
    }
}
