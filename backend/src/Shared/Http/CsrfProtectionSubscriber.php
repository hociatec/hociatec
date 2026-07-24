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
    /**
     * @var list<string>
     */
    private const EXCLUDED_PREFIXES = [
        '/api/csrf-token',
        '/api/auth/login',
        '/api/auth/logout',
        '/api/auth/refresh',
        '/api/auth/register',
        '/api/auth/verify',
        '/api/auth/password-reset',
        '/api/stripe/webhook',
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

            foreach (self::EXCLUDED_PREFIXES as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
