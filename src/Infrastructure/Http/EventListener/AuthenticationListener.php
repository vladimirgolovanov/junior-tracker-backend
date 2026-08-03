<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\EventListener;

use App\Application\Auth\AuthenticateByToken;
use App\Domain\Auth\Exception\Unauthenticated;
use App\Infrastructure\Http\Attribute\Authenticated;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Аутентификация роутов, помеченных #[Authenticated]: читает Bearer-токен
 * и кладёт id пользователя в атрибуты запроса под USER_ID_ATTRIBUTE.
 */
#[AsEventListener(event: KernelEvents::CONTROLLER)]
final readonly class AuthenticationListener
{
    public const USER_ID_ATTRIBUTE = 'auth_user_id';

    public function __construct(
        private AuthenticateByToken $authenticateByToken,
    ) {
    }

    public function __invoke(ControllerEvent $event): void
    {
        if ([] === $event->getAttributes(Authenticated::class)) {
            return;
        }

        $request = $event->getRequest();
        $token = $this->bearerToken($request->headers->get('Authorization'));

        $userId = ($this->authenticateByToken)($token, new \DateTimeImmutable('now'));

        $request->attributes->set(self::USER_ID_ATTRIBUTE, $userId);
    }

    private function bearerToken(?string $header): string
    {
        if (null === $header || !preg_match('/^Bearer\s+(\S+)$/', $header, $matches)) {
            throw Unauthenticated::missingBearer();
        }

        return $matches[1];
    }
}
