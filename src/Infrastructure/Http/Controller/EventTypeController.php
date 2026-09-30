<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Event\EventTypeMapper;
use App\Application\Event\ListEventTypes;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\ChildIdQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class EventTypeController
{
    public function __construct(
        private readonly ListEventTypes $listEventTypes,
        private readonly EventTypeMapper $mapper,
    ) {
    }

    #[Route('/api/v2/event_types/', name: 'api_v2_event_types', methods: ['GET'])]
    #[Authenticated]
    public function __invoke(
        Request $request,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ChildIdQuery $query,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $eventTypes = $this->listEventTypes->handle($userId, (int) $query->child_id);

        return new JsonResponse($this->mapper->toArray($eventTypes));
    }
}
