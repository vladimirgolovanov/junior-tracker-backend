<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Export\ExportMapper;
use App\Application\Export\GetExport;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Status of a single export job. Owner-only (enforced in the use-case).
 */
final class GetExportController
{
    public function __construct(
        private readonly GetExport $getExport,
        private readonly ExportMapper $mapper,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/exports/{exportId}',
        name: 'api_v2_child_export_get',
        requirements: ['childId' => '\d+', 'exportId' => '\d+'],
        methods: ['GET'],
    )]
    #[Authenticated]
    public function __invoke(int $childId, int $exportId, Request $request): JsonResponse
    {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        return new JsonResponse($this->mapper->one(($this->getExport)($userId, $childId, $exportId)));
    }
}
