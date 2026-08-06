<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Event\EventMapper;
use App\Application\Event\ListEvents;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\EventsQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final class ListEventsController
{
    public function __construct(
        private readonly ListEvents $listEvents,
        private readonly EventMapper $mapper,
    ) {
    }

    #[Route('/api/v2/events', name: 'api_v2_events', methods: ['GET'])]
    #[Authenticated]
    public function __invoke(
        Request $request,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        EventsQuery $query,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $events = ($this->listEvents)($userId, (int) $query->child_id, $query->limit, $query->offset);

        return new JsonResponse($this->mapper->toArray($events));
    }
}
