<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

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

            if ($this->isControllerExempt($request)) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function isControllerExempt(Request $request): bool
    {
        $controller = $request->attributes->get('_controller');
        if (!is_string($controller)) {
            return false;
        }

        [$class, $method] = str_contains($controller, '::')
            ? explode('::', $controller, 2)
            : [$controller, '__invoke'];

        if (!class_exists($class)) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($class);
        if ([] !== $reflectionClass->getAttributes(CsrfExempt::class)) {
            return true;
        }

        if (!$reflectionClass->hasMethod($method)) {
            return false;
        }

        return [] !== $reflectionClass->getMethod($method)->getAttributes(CsrfExempt::class);
    }
}
