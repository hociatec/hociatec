<?php

declare(strict_types=1);

namespace App\Module\Auth\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class ActivationRedirectController
{
    public function __construct(private readonly string $frontendUrl)
    {
    }

    #[Route('/activation/{token}', name: 'app_activation_redirect', methods: ['GET'])]
    public function __invoke(string $token): RedirectResponse
    {
        $target = rtrim($this->frontendUrl, '/').'/activation/'.$token;

        return new RedirectResponse($target, 302);
    }
}
