<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Sleep\DaySummaryMapper;
use App\Application\Sleep\GetSleepSummary;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class SleepSummaryController
{
    public function __construct(
        private readonly GetSleepSummary $getSleepSummary,
        private readonly DaySummaryMapper $mapper,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/sleep-summaries',
        name: 'api_v2_sleep_summaries',
        requirements: ['childId' => '\d+'],
        methods: ['GET'],
    )]
    public function __invoke(int $childId, Request $request): JsonResponse
    {
        $from = $this->parseDate($request->query->get('from'), 'from');
        $to = $this->parseDate($request->query->get('to'), 'to');

        if ($to < $from) {
            throw new BadRequestHttpException('"to" must not be earlier than "from".');
        }

        if ($from->diff($to)->days >= count(DaySummaryMapper::DAY_KEYS)) {
            throw new BadRequestHttpException('Date range must not exceed 3 days.');
        }

        $summaries = $this->getSleepSummary->forRange(
            childId: $childId,
            firstDay: $from,
            lastDay: $to,
            now: new \DateTimeImmutable('now'),
        );

        return new JsonResponse($this->mapper->toKeyedArray($summaries));
    }

    private function parseDate(?string $value, string $field): \DateTimeImmutable
    {
        if (null === $value) {
            throw new BadRequestHttpException(sprintf('Query parameter "%s" is required.', $field));
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (false === $date) {
            throw new BadRequestHttpException(sprintf('"%s" must be a valid date (YYYY-MM-DD).', $field));
        }

        return $date;
    }
}
