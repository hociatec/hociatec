<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ActivationRedirectController
{
    #[Route('/activation/{token}', name: 'app_activation_redirect', methods: ['GET'])]
    public function __invoke(string $token): RedirectResponse
    {
        $frontendUrl = $_ENV['APP_FRONTEND_URL'] ?? 'http://localhost:5173';
        $target = rtrim($frontendUrl, '/') . '/activation/' . $token;

        return new RedirectResponse($target, 302);
    }
}

