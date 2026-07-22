<?php

declare(strict_types=1);

namespace App\Shared\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiErrorSanitizerSubscriber implements EventSubscriberInterface
{
    private const INTERNAL_ERROR_MESSAGE = 'Une erreur interne est survenue.';
    private const BAD_REQUEST_MESSAGE = 'Requête impossible.';

    public function __construct(private readonly string $environment)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -70],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if ($this->environment !== 'prod') {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        if (!str_starts_with($request->getPathInfo(), '/api/') || !$response instanceof JsonResponse) {
            return;
        }

        $payload = json_decode((string) $response->getContent(), true);
        if (!is_array($payload) || ($payload['status'] ?? null) !== 'error') {
            return;
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode >= 500) {
            $payload['message'] = self::INTERNAL_ERROR_MESSAGE;
            $payload['details'] = [];
            $response->setData($payload);

            return;
        }

        if (isset($payload['message']) && is_string($payload['message']) && $this->containsSensitiveText($payload['message'])) {
            $payload['message'] = self::BAD_REQUEST_MESSAGE;
        }

        if (isset($payload['details']) && $this->containsSensitiveText($payload['details'])) {
            $payload['details'] = [];
        }

        $response->setData($payload);
    }

    private function containsSensitiveText(mixed $value): bool
    {
        if (is_array($value)) {
            foreach ($value as $item) {
                if ($this->containsSensitiveText($item)) {
                    return true;
                }
            }

            return false;
        }

        if (!is_scalar($value)) {
            return false;
        }

        return preg_match(
            '/(SQLSTATE|PDOException|Doctrine\\\\|Symfony\\\\|SELECT\\s|INSERT\\s|UPDATE\\s|DELETE\\s|\\/home\\/|\\/var\\/|APP_SECRET|DATABASE_URL|JWT_|STRIPE_|MAILER_DSN|stack trace|Trace:)/i',
            (string) $value,
        ) === 1;
    }
}
