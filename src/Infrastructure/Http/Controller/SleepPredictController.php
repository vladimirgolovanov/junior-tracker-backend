<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Sleep\GetSleepPredictions;
use App\Application\Sleep\SleepPredictionMapper;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\ChildIdQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Predicted sleep/awake segments for the rest of the child's day.
 */
final class SleepPredictController
{
    public function __construct(
        private readonly GetSleepPredictions $getSleepPredictions,
        private readonly SleepPredictionMapper $mapper,
    ) {
    }

    #[Route('/api/v2/sleep-predict', name: 'api_v2_sleep_predict', methods: ['GET'])]
    #[Authenticated]
    public function __invoke(
        Request $request,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ChildIdQuery $query,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $predictions = $this->getSleepPredictions->handle($userId, (int) $query->child_id);

        return new JsonResponse($this->mapper->toArray($predictions));
    }
}
