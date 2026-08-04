<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CsrfProtectionSubscriber implements EventSubscriberInterface
{
    /** @var list<string> */
    private const EXCLUDED_PATHS = [
        '/api/auth/login',
    ];

    /** @var list<string> */
    private const EXCLUDED_ROUTES = [
        'api_auth_password_reset_confirm',
        'api_auth_password_reset_request',
        'api_auth_refresh',
        'api_auth_register',
        'api_stripe_webhook',
    ];

    public function __construct(private readonly CsrfTokenService $csrfTokenService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 8],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->requiresCsrf($request)) {
            return;
        }

        if (!$this->csrfTokenService->isValid($request)) {
            $event->setResponse(
                ApiResponse::error('Jeton CSRF invalide ou manquant.', Response::HTTP_FORBIDDEN)
            );
        }
    }

    private function requiresCsrf(Request $request): bool
    {
        if (!$request->isMethodSafe()) {
            $path = $request->getPathInfo();
            if (!str_starts_with($path, '/api/')) {
                return false;
            }

            if (in_array($path, self::EXCLUDED_PATHS, true)) {
                return false;
            }

            $route = $request->attributes->get('_route');
            if (is_string($route) && in_array($route, self::EXCLUDED_ROUTES, true)) {
                return false;
            }

            return true;
        }

        return false;
    }
}
