<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Temporary reverse proxy: forwards /api/v2/events/* to the old backend at /api/events/*.
 *
 * Some event endpoints are not yet finalized in this service, so for now they are served
 * by the old FastAPI backend. Both backends share the same opaque Bearer token
 * (access_tokens table), so the Authorization header is forwarded unchanged and the old
 * backend performs the authentication itself — this controller is a plain passthrough.
 *
 * The catch-all routes use priority -10 so that native routes (e.g. POST /api/v2/events/range)
 * keep matching first. Remove this controller and re-enable the native routes once the
 * endpoints are implemented here.
 */
final class EventsProxyController
{
    private const PATH_PREFIX = '/api/v2/events';
    private const LEGACY_PREFIX = '/api/events';
    private const METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    /**
     * Hop-by-hop headers that must not be forwarded to the upstream request.
     */
    private const SKIP_REQUEST_HEADERS = [
        'host',
        'content-length',
        'connection',
        'keep-alive',
        'transfer-encoding',
        'te',
        'trailer',
        'upgrade',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $legacyBackendUrl,
    ) {
    }

    // A single catch-all with an optional {path} so it also matches the bare collection
    // both without and with a trailing slash (/api/v2/events and /api/v2/events/), which
    // is the trailing-slash convention the old backend and the frontend use.
    #[Route(
        self::PATH_PREFIX.'/{path}',
        name: 'proxy_events',
        requirements: ['path' => '.*'],
        defaults: ['path' => ''],
        methods: self::METHODS,
        priority: -10,
    )]
    public function __invoke(Request $request): Response
    {
        $targetPath = preg_replace(
            '#^'.preg_quote(self::PATH_PREFIX, '#').'#',
            self::LEGACY_PREFIX,
            $request->getPathInfo(),
        );

        $queryString = $request->getQueryString();
        $targetUrl = rtrim($this->legacyBackendUrl, '/').$targetPath;
        if (null !== $queryString && '' !== $queryString) {
            $targetUrl .= '?'.$queryString;
        }

        try {
            $upstream = $this->httpClient->request(
                $request->getMethod(),
                $targetUrl,
                [
                    'headers' => $this->forwardHeaders($request),
                    'body' => $request->getContent(),
                    'max_redirects' => 0,
                ],
            );

            // Pass false so 4xx/5xx responses are returned as-is instead of throwing.
            $statusCode = $upstream->getStatusCode();
            $body = $upstream->getContent(false);
            $contentType = $upstream->getHeaders(false)['content-type'][0] ?? 'application/json';
        } catch (TransportExceptionInterface $e) {
            return new JsonResponse(
                [
                    'type' => 'about:blank',
                    'title' => 'Bad Gateway',
                    'status' => Response::HTTP_BAD_GATEWAY,
                    'detail' => 'Upstream events backend is unreachable.',
                ],
                Response::HTTP_BAD_GATEWAY,
                ['Content-Type' => 'application/problem+json'],
            );
        }

        return new Response($body, $statusCode, ['Content-Type' => $contentType]);
    }

    /**
     * @return array<string, string>
     */
    private function forwardHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            if (\in_array(strtolower($name), self::SKIP_REQUEST_HEADERS, true)) {
                continue;
            }

            $headers[$name] = implode(', ', array_filter($values, static fn (?string $v): bool => null !== $v));
        }

        return $headers;
    }
}
