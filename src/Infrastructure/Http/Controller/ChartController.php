<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Chart\ChartMapper;
use App\Application\Chart\GetChart;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\ChartQuery;
use App\Infrastructure\Http\Request\RepeatedQueryParam;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Таймлайн сна за период плюс отметки выбранных типов событий.
 */
final class ChartController
{
    private const ADDITIONAL_DATA_IDS = 'additional_data_ids';

    public function __construct(
        private readonly GetChart $getChart,
        private readonly ChartMapper $mapper,
    ) {
    }

    #[Route('/api/v2/chart', name: 'api_v2_chart', methods: ['GET'])]
    #[Authenticated]
    public function __invoke(
        Request $request,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        ChartQuery $query,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $chart = $this->getChart->handle(
            $userId,
            (int) $query->child_id,
            new \DateTimeImmutable((string) $query->date_from),
            new \DateTimeImmutable((string) $query->date_to),
            RepeatedQueryParam::ints($request->server->get('QUERY_STRING'), self::ADDITIONAL_DATA_IDS),
        );

        return new JsonResponse($this->mapper->toArray($chart));
    }
}
