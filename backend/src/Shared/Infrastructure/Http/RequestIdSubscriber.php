<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Http\RequestContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class RequestIdSubscriber implements EventSubscriberInterface
{
    public const HEADER = 'X-Request-Id';
    public const ATTRIBUTE = RequestContext::REQUEST_ID_ATTRIBUTE;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
            KernelEvents::RESPONSE => ['onKernelResponse', -100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $incoming = $request->headers->get(self::HEADER);
        $requestId = is_string($incoming) && '' !== $incoming ? $incoming : $this->generateId();
        $request->attributes->set(self::ATTRIBUTE, $requestId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestId = (string) ($request->attributes->get(self::ATTRIBUTE) ?? '');
        if ('' !== $requestId) {
            $response->headers->set(self::HEADER, $requestId);
        }
    }

    private function generateId(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Random\RandomException) {
            return (string) microtime(true);
        }
    }
}
