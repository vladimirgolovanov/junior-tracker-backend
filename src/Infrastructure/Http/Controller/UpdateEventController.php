<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Event\EventMapper;
use App\Application\Event\UpdateEvent;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\UpdateEventRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class UpdateEventController
{
    public function __construct(
        private readonly UpdateEvent $updateEvent,
        private readonly EventMapper $mapper,
    ) {
    }

    #[Route('/api/v2/events/{id}', name: 'api_v2_events_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    #[Authenticated]
    public function __invoke(
        int $id,
        Request $request,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        UpdateEventRequest $payload,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $event = ($this->updateEvent)($userId, $id, $payload->toPatch());

        return new JsonResponse($this->mapper->one($event));
    }
}
