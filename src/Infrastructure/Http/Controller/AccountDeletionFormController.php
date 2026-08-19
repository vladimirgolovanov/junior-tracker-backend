<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Infrastructure\Http\Page\AccountDeletionPage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public entry point for the URL submitted to Play Console under
 * App content -> Data safety -> Data deletion. Deliberately unauthenticated:
 * the policy requires it to work without the app installed.
 */
final class AccountDeletionFormController
{
    public function __construct(
        private readonly AccountDeletionPage $page,
    ) {
    }

    #[Route('/delete-account', name: 'account_deletion_form', methods: ['GET'])]
    public function __invoke(): Response
    {
        return new Response(
            $this->page->form(),
            Response::HTTP_OK,
            ['Content-Type' => 'text/html; charset=utf-8'],
        );
    }
}
