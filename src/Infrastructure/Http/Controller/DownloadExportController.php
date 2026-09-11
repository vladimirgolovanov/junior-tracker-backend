<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Export\DownloadExport;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Streams a completed export file. Owner-only (enforced in the use-case). The
 * backend proxies the bytes from storage, so the object store stays hidden.
 */
final class DownloadExportController
{
    public function __construct(
        private readonly DownloadExport $downloadExport,
    ) {
    }

    #[Route(
        '/api/v2/children/{childId}/exports/{exportId}/download',
        name: 'api_v2_child_export_download',
        requirements: ['childId' => '\d+', 'exportId' => '\d+'],
        methods: ['GET'],
    )]
    #[Authenticated]
    public function __invoke(int $childId, int $exportId, Request $request): StreamedResponse
    {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $download = ($this->downloadExport)($userId, $childId, $exportId);

        $response = new StreamedResponse(static function () use ($download): void {
            foreach ($download->chunks as $chunk) {
                echo $chunk;
            }
        }, Response::HTTP_OK);

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $download->filename),
        );

        return $response;
    }
}
