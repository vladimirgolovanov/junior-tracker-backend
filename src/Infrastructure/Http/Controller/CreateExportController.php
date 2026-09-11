<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Export\ExportMapper;
use App\Application\Export\RequestExport;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\CreateExportRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Queues an export of a child's data. Owner-only (enforced in the use-case).
 * Returns 202 with the pending job; the file is produced asynchronously.
 */
final class CreateExportController
{
    public function __construct(
        private readonly RequestExport $requestExport,
        private readonly ExportMapper $mapper,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/exports',
        name: 'api_v2_child_exports_create',
        requirements: ['childId' => '\d+'],
        methods: ['POST'],
    )]
    #[Authenticated]
    public function __invoke(
        int $childId,
        Request $request,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        CreateExportRequest $payload,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $export = ($this->requestExport)(
            $userId,
            $childId,
            $payload->type(),
            $payload->from(),
            $payload->to(),
        );

        return new JsonResponse($this->mapper->one($export), Response::HTTP_ACCEPTED);
    }
}
