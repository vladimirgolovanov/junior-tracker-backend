<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Registration\RegisterUser;
use App\Domain\Child\ValueObject\Timezone;
use App\Domain\Shared\Exception\InvalidValue;
use App\Domain\User\ValueObject\Credentials;
use App\Domain\User\ValueObject\Email;
use App\Domain\User\ValueObject\PlainPassword;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterController
{
    public function __construct(
        private readonly RegisterUser $registerUser,
    ) {
    }

    #[Route('/api/v2/register', name: 'api_v2_register', methods: ['POST'])]
    #[RateLimit(limiter: 'registration')]
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->decodeBody($request);

        $credentials = new Credentials(
            new Email($this->stringField($payload, 'email')),
            new PlainPassword($this->stringField($payload, 'password')),
        );

        $inviteCode = $this->optionalStringField($payload, 'invite');
        $now = new \DateTimeImmutable('now');

        // Таймзону спрашиваем только при обычной регистрации: она нужна новому
        // ребёнку. При присоединении по приглашению поля timezone во фронте нет.
        $registered = null !== $inviteCode
            ? $this->registerUser->joinChildByInvite($credentials, $inviteCode, $now)
            : $this->registerUser->register(
                $credentials,
                new Timezone($this->stringField($payload, 'timezone')),
                $now,
            );

        return new JsonResponse(
            [
                'id' => $registered->userId,
                'email' => $credentials->email->value,
                'child_id' => $registered->childId,
            ],
            Response::HTTP_CREATED,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBody(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable) {
            throw new BadRequestHttpException('Request body must be a JSON object.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function stringField(array $payload, string $field): string
    {
        if (!array_key_exists($field, $payload)) {
            throw InvalidValue::missingField($field);
        }

        if (!is_string($payload[$field])) {
            throw InvalidValue::notAString($field);
        }

        return $payload[$field];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function optionalStringField(array $payload, string $field): ?string
    {
        if (!array_key_exists($field, $payload)) {
            return null;
        }

        if (!is_string($payload[$field])) {
            throw InvalidValue::notAString($field);
        }

        return $payload[$field];
    }
}
