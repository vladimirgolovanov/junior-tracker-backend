<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Feedback\FeedbackMapper;
use App\Application\Feedback\SubmitFeedback;
use App\Infrastructure\Http\Attribute\Authenticated;
use App\Infrastructure\Http\EventListener\AuthenticationListener;
use App\Infrastructure\Http\Request\SubmitFeedbackRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CreateFeedbackController
{
    public function __construct(
        private readonly SubmitFeedback $submitFeedback,
        private readonly FeedbackMapper $mapper,
    ) {
    }

    #[Route('/api/v2/feedback', name: 'api_v2_feedback_create', methods: ['POST'])]
    #[Authenticated]
    public function __invoke(
        Request $request,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)]
        SubmitFeedbackRequest $payload,
    ): JsonResponse {
        $userId = $request->attributes->getInt(AuthenticationListener::USER_ID_ATTRIBUTE);

        $feedback = $this->submitFeedback->handle($userId, (string) $payload->text);

        return new JsonResponse($this->mapper->one($feedback), Response::HTTP_CREATED);
    }
}
