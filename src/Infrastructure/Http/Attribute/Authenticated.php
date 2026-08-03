<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Attribute;

/**
 * Метит контроллер как требующий валидный Bearer-токен.
 * Обрабатывается в AuthenticationListener.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Authenticated
{
}
