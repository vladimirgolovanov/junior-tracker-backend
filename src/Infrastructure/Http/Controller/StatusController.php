<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Status\ChildStatusMapper;
use App\Application\Status\GetChildStatus;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\ChildIdQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Лёгкий «пульс» ребёнка: фронт дёргает его раз в минуту с любой страницы.
 */
final class StatusController
{
    public function __construct(
        private readonly GetChildStatus $getChildStatus,
        private readonly ChildStatusMapper $mapper,
    ) {
    }

    #[Route('/api/v2/status', name: 'api_v2_status', methods: ['GET'])]
    #[Authenticated]
    public function __invoke(
        Request $request,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ChildIdQuery $query,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $status = $this->getChildStatus->handle(
            $userId,
            (int) $query->child_id,
            new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
        );

        return new JsonResponse($this->mapper->toArray($status));
    }
}
