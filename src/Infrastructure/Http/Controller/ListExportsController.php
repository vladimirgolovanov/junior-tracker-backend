<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Export\ExportMapper;
use App\Application\Export\ListExports;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Export history of a child. Owner-only (enforced in the use-case).
 */
final class ListExportsController
{
    public function __construct(
        private readonly ListExports $listExports,
        private readonly ExportMapper $mapper,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/exports',
        name: 'api_v2_child_exports_list',
        requirements: ['childId' => '\d+'],
        methods: ['GET'],
    )]
    #[Authenticated]
    public function __invoke(int $childId, Request $request): JsonResponse
    {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        return new JsonResponse($this->mapper->toArray(($this->listExports)($userId, $childId)));
    }
}
