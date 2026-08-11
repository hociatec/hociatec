<?php

declare(strict_types=1);

namespace App\Module\Auth\UI\Controller;

use App\Shared\Infrastructure\Http\OutboundUrlGuard;
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
        OutboundUrlGuard::assertAllowedHttpsRedirectBase($this->frontendUrl);
        $target = rtrim($this->frontendUrl, '/').'/activation/'.rawurlencode($token);

        return new RedirectResponse($target, 302);
    }
}
