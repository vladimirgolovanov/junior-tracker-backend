<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Domain\ChildInvite\Exception\InviteAlreadyAccepted;
use App\Domain\ChildInvite\Exception\InviteExpired;
use App\Domain\ChildInvite\Exception\InviteNotFound;
use App\Domain\Registration\Exception\EmailAlreadyRegistered;
use App\Domain\Shared\Exception\InvalidValue;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Отдаёт ошибки публичного контура в формате problem+json (RFC 7807).
 * Только для /api/v2: у внутренних роутов поведение не меняется.
 */
#[AsEventListener]
final readonly class ProblemJsonExceptionListener
{
    private const PUBLIC_PREFIX = '/api/v2/';

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), self::PUBLIC_PREFIX)) {
            return;
        }

        $response = $this->toResponse($event->getThrowable());

        if (null !== $response) {
            $event->setResponse($response);
        }
    }

    private function toResponse(\Throwable $exception): ?JsonResponse
    {
        return match (true) {
            $exception instanceof InvalidValue => $this->problem(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'Validation failed',
                ['errors' => [$exception->field => $exception->getMessage()]],
            ),
            $exception instanceof EmailAlreadyRegistered => $this->problem(
                Response::HTTP_CONFLICT,
                'Email already registered',
            ),
            $exception instanceof InviteNotFound => $this->problem(
                Response::HTTP_NOT_FOUND,
                'Invite not found',
            ),
            $exception instanceof InviteExpired => $this->problem(
                Response::HTTP_GONE,
                'Invite expired',
            ),
            $exception instanceof InviteAlreadyAccepted => $this->problem(
                Response::HTTP_CONFLICT,
                'Invite already accepted',
            ),
            $exception instanceof HttpExceptionInterface => $this->problem(
                $exception->getStatusCode(),
                Response::$statusTexts[$exception->getStatusCode()] ?? 'Error',
                headers: $exception->getHeaders(),
            ),
            // Всё остальное — необработанная ошибка; пусть с ней разбирается
            // стандартный обработчик, чтобы не терять трейс в dev.
            default => null,
        };
    }

    /**
     * @param array<string, mixed>  $extra
     * @param array<string, string> $headers
     */
    private function problem(int $status, string $title, array $extra = [], array $headers = []): JsonResponse
    {
        $response = new JsonResponse(
            ['title' => $title, 'status' => $status] + $extra,
            $status,
            $headers,
        );

        $response->headers->set('Content-Type', 'application/problem+json');

        return $response;
    }
}
